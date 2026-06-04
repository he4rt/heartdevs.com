# ADR-0006: Numeric code check-in

## Status

Accepted — 2026-05-31

## Context

Participants need a check-in method beyond manual organizer intervention. Numeric codes — short-lived digit strings announced by the organizer — allow self-service check-in during live events. The code is bound to a specific event date, has a validity window and optional max uses, and supports bot-triggered check-in via domain events (ADR-0005).

## Decision

### Action pattern

Introduce `NumericCodeCheckInAction` following the same delegation pattern as `ManualCheckInAction`. The new action validates the code (existence, date match, time window, max uses, revoked status), atomically increments `uses_count` on the code record, then delegates to the core `CheckInAction` with `CheckInMethod::NumericCode` and `TriggeredBy::User`. This keeps all core check-in logic (status transition, duplicate detection, domain event dispatch) in the single `CheckInAction`.

### Soft revoke instead of delete

Codes receive a nullable `revoked_at` timestamp. Revoked codes fail validation identically to expired codes. This preserves the audit trail — an organizer can see which codes were used before revocation, which aligns with ADR-0003 (no data destruction).

### Admin UI as RelationManager

A `CheckInCodesRelationManager` on the Event edit page (alongside the existing `EnrollmentsRelationManager`). No separate Filament resource — codes are always scoped to an event. The form includes a digit length selector (4 or 6), auto-generates a random read-only code, defaults `event_date` to the event's `starts_at` date (organizer can override, must be within event range), and provides a revoke action.

### Participant UI as dedicated Livewire component

A `NumericCodeCheckIn` Livewire component embedded in the event detail page. Shown only when the user's enrollment status is `confirmed` or `checked_in`. A text input for the code plus a submit button; validation errors surface as inline messages.

### Error messages

Each failure mode gets a distinct error message for clear diagnostics:

| Condition                                                     | Message                         | HTTP |
| ------------------------------------------------------------- | ------------------------------- | ---- |
| Code not found in database                                    | "Invalid check-in code"         | 422  |
| Code found but `event_date` does not match check-in date      | "Code is not valid for today"   | 422  |
| Code found but `expires_at` has passed or `revoked_at` is set | "Code has expired"              | 422  |
| Code found but `uses_count >= max_uses`                       | "Code has reached maximum uses" | 422  |

### Code validation order

1. Existence (code not found → invalid)
2. Date binding (found but wrong date → not valid for today)
3. Expiry/revocation (found, right date, but expired/revoked → expired)
4. Max uses (found, right date, still valid, but exhausted → max uses)
5. Uses increment (found, right date, valid, has capacity → atomically increment and proceed)

## Consequences

- **Separate concerns** — code validation is isolated from check-in mechanics; `CheckInAction` remains untouched.
- **Self-service** — participants check in without organizer intervention; scales to large events.
- **Concurrency-safe** — `uses_count` increment is atomic inside a DB transaction with locked rows.
- **Bot compatible** — Discord/Twitch bot can validate codes by dispatching `CheckInRequested` domain events (future).
- **Multi-day** — organizer creates one code per day with the appropriate `event_date`.
