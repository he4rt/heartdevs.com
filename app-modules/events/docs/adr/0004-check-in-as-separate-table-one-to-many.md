# ADR-0004: Check-ins as separate 1:N table per enrollment

## Status

Accepted — 2026-05-16

## Context

Initially considered storing check-in data as columns on the enrollment record (1:1). However, multi-day events (e.g., 2-day conference) require multiple check-ins per enrollment — one per day attended.

## Decision

`events_check_ins` is a separate table with a many-to-one relationship to enrollments. Each record represents one check-in on one specific date. The enrollment policy's `attendance_requirement` (`all_days` | `any_day` | `minimum_days(N)`) determines how many check-ins are needed for the `attended` terminal state.

## Consequences

- **Multi-day supported** — conferences spanning N days work naturally.
- **Event days derived from date range** — no separate `events_days` table. System validates `check_in_date BETWEEN event.starts_at::date AND event.ends_at::date`.
- **QR token reuse** — one QR per enrollment, each scan on a different day creates a new check-in record.
- **Numeric codes bound to date** — `events_check_in_codes.event_date` ensures codes are day-specific.
- **Trade-off**: if an event skips a day (Friday + Sunday, no Saturday), the system can't distinguish "valid day" from "gap day" without manual intervention. Acceptable for current use cases (consecutive days).
