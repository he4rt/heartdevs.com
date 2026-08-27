# ADR-0007: Event closure as scheduled job

## Status

Accepted — 2026-06-04

## Context

After an event's `ends_at`, enrollments must be resolved to terminal states: `attended` (success, when `attendance_requirement` is satisfied per the policy) or `no_show` (failure, when not). Without automated closure, enrollments stay in `confirmed` or `checked_in` indefinitely, blocking `ParticipantAttended` dispatch and the XP reward that Gamification awards on that event (ADR-0002). Manual organizer action does not scale, and real-time closure on `ends_at` is fragile (clock drift, job queue downtime, multi-day events whose `ends_at` is the final day).

The closure step is also the natural place to mark `confirmed` participants who never checked in as `no_show`. This is a system-driven consequence, not an organizer decision.

## Decision

### Closure is a post-event scheduled sweep

A scheduled job runs after events end and resolves every non-terminal enrollment to a terminal state. This is decoupled from any real-time trigger on `ends_at` so that queue downtime, clock drift, or off-by-one date math cannot leave enrollments stranded.

### Per-event job granularity

`ProcessEventClosureJob` handles one event at a time (constructor takes `string $eventId`). The job implements `ShouldQueue` and `ShouldBeUnique` with `uniqueId = $eventId` and `uniqueFor = 1800` seconds. This means concurrent dispatches for the same event collapse into a single execution, and the lock covers the full retry window (backoff sum ≈ 16 minutes, plus buffer).

`$backoff = [1, 5, 10]` and `tries = 4` (1 initial + 3 retries). After exhaustion, `failed()` runs and logs the `event_id` plus the exception message.

### Per-enrollment transactions, not per-event

`CloseEventAction::handle(Event $event): int` opens one `DB::transaction` per enrollment inside its loop, not one transaction wrapping the entire event. This is a deliberate departure from the "all-or-nothing per operation" default in favour of **partial commits that preserve audit trails on failure**.

The loop is:

1. Re-load the enrollment with `lockForUpdate()`.
2. If the enrollment is already terminal (`isTerminal()` returns true — e.g. a previous attempt succeeded, or an admin override landed between attempts), skip it.
3. Resolve the target status: `EnrollmentPolicy::resolveAttendance($enrollment)` for `checked_in` (returns `Attended` or `NoShow`), or `NoShow` directly for `confirmed`.
4. Call the existing `TransitionEnrollmentAction` (ADR-0001, ADR-0003) with `triggered_by = System`. This writes the audit row and updates the status + timestamp atomically.
5. If the target is `Attended`, dispatch `ParticipantAttended` (mirroring `ParticipantCheckedIn` payload shape — IDs, not models).

Idempotency is therefore structural: the `isTerminal()` re-check + `canTransitionTo()` guard + `lockForUpdate()` make a second run a no-op for already-closed enrollments.

### Discovery via artisan command

A `ClosePendingEventsCommand` (`events:close-pending`) queries `Event` where `ends_at < now()` AND has at least one enrollment in `confirmed` or `checked_in`, then dispatches one `ProcessEventClosureJob` per match. Scheduled every 15 minutes in `EventsServiceProvider::boot()` via `Schedule::command(...)->everyFifteenMinutes()->withoutOverlapping()`.

This is the same pattern `IntegrationDevToServiceProvider` uses for its own scheduled command, so the discovery mechanism is testable (`Artisan::call`) and manually runnable.

## Consequences

- **Audit trail on partial failure** — if the 3rd enrollment in a 100-enrollment event throws mid-loop, the first two keep their `EnrollmentTransition` records. The retry can pick up from the 3rd without re-doing work or losing history.
- **Concurrency-safe** — `ShouldBeUnique` prevents two jobs processing the same event, even if the scheduler fires twice in a race.
- **Retry-friendly** — completed enrollments are skipped via the `isTerminal()` re-check, so retries do not re-dispatch `ParticipantAttended` or write duplicate transitions.
- **Worst-case latency** — `ends_at` to closure is at most 15 minutes (one scheduler tick) plus job queue latency. Acceptable for terminal-state assignment, which is not user-facing in real time.
- **Trade-off** — a 3-retry ceiling means persistent infrastructure failures (DB outage spanning >16 minutes) end up in the `failed()` log instead of auto-recovery. The command will redispatch on the next scheduler tick, so eventual closure is still likely without human intervention.
- **Trade-off** — the 15-minute cadence means a `ParticipantAttended` event may fire up to 15 minutes after the event ends. Gamification's XP grant follows the same window. Documented in `EnrollmentPolicy::$xp_on_attended` consumers (Gamification listeners).
