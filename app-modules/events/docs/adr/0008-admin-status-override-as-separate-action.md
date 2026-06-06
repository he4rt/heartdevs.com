# ADR-0008: Admin status override as separate Action

## Status

Accepted — 2026-06-04

## Context

The enrollment state machine (ADR-0001) blocks all transitions out of terminal states. This is correct for the normal lifecycle: an `attended` enrollment should never revert to `checked_in`, and `no_show` should never silently become `attended`. But real-world organizer needs include:

- A participant marked `no_show` who actually attended (organizer missed a check-in, code expired, manual check-in failed silently).
- A late arrival who confirmed but missed the formal check-in window and the organizer wants to mark them `checked_in` to record the presence.

These are **admin corrections**, not normal lifecycle transitions. They need a different validation surface, a stronger audit story, and a clear rule that they bypass the state machine for a tightly-scoped set of cases.

## Decision

### Override is a separate Action

Introduce `OverrideEnrollmentStatusAction` as a sibling to `TransitionEnrollmentAction`, not a force-flag on it. The two actions have different validation, different intent, and different side effects. A force flag on `TransitionEnrollmentAction` would have to be threaded through `TransitionEnrollmentDTO`, the transition audit, and any future caller — and would silently widen the surface that can bypass the state machine.

### Allowed overrides are explicit and narrow

The Action validates the `(fromStatus, toStatus)` pair against an explicit allowlist:

| From        | To           | Use case                                 |
| ----------- | ------------ | ---------------------------------------- |
| `no_show`   | `attended`   | Organizer corrects a missed check-in     |
| `confirmed` | `checked_in` | Late arrival that missed formal check-in |

Any other pair — including `attended → no_show`, `cancelled → attended`, or any path starting from `attended`/`cancelled`/`rejected` — is rejected with `OverrideEnrollmentStatusException::overrideNotAllowed(from, to)`.

### Reason is required and recorded

`OverrideEnrollmentStatusDTO` takes a non-empty `reason` string and rejects empty input with `OverrideEnrollmentStatusException::reasonRequired()`. The reason is written to `EnrollmentTransition::$reason` for accountability, alongside `triggered_by = admin` and `actor_id = $organizer->id`.

### Stale status is a conflict, not a generic invalid override

The DTO carries the enrollment status observed by the admin UI. The Action still re-loads the enrollment inside a transaction with a row lock before applying the correction. If the current status no longer matches the observed status, it throws `OverrideEnrollmentStatusException::statusChanged(expected, actual)` instead of `overrideNotAllowed(from, to)`.

This separates two cases:

- `overrideNotAllowed` — the requested pair is not part of the correction allowlist.
- `statusChanged` — the requested pair may have been valid when the admin opened the modal, but another process changed the enrollment before save.

### Override does not re-dispatch domain events

No `ParticipantAttended` or `ParticipantCheckedIn` is dispatched on override. The override is a correction of the audit trail, not a fresh occurrence. XP implications:

- `no_show → attended` does not grant XP retroactively. The participant was marked absent by the closure job; admin correction updates the record but the system-level "first time attended" event has already passed.
- `confirmed → checked_in` does not dispatch `ParticipantCheckedIn`. Same reasoning — the check-in did not happen through the normal flow, and the system event has semantic meaning tied to actual check-in mechanics.

If retroactive XP is ever needed, it is a separate decision (likely a `ParticipantManuallyAttended` event, with its own audience in Gamification).

### Filament UI is a dedicated Action class

`OverrideEnrollmentStatusAction` lives under the Event relation manager `Actions/` directory, matching the existing `GenerateCheckInCodeAction` / `RevokeCheckInCodeAction` pattern. It opens a modal with a reason textarea and a status select pre-populated with the allowed targets. The action is visible only when the current status is in the allowlist source set (`no_show`, `confirmed`) — anything else hides the action entirely.

### No runtime inconsistency checks

The form and factory enforce that `minimum_days` is set when `attendance_requirement = MinimumDays`. There is no save-time guard on `EnrollmentPolicy` for inconsistent `(requirement, minimum_days)` — this is defense at the edges only. See ADR-0007 for the closure-side evaluator that trusts the data.

## Consequences

- **Strong accountability** — every override writes an audit row with the organizer's identity and their stated reason.
- **Tightly scoped bypass** — the state machine is still the gatekeeper for all non-override transitions. The override path is the _only_ way to leave a terminal state, and it is one line in one Action.
- **No XP duplication risk** — by not re-dispatching domain events, an admin override cannot grant XP twice for the same participant-event pair.
- **Trade-off** — overriding a status that has downstream side effects beyond XP (e.g. waitlist promotion triggered by `confirmed → cancelled`) does not replay those side effects. Acceptable for the current scope; flag if a new side-effect is added.
- **Trade-off** — the Filament action is now another class to navigate, but `EnrollmentsRelationManager` stays focused on table composition while action-specific form and callback wiring live next to the other Event relation manager actions.
