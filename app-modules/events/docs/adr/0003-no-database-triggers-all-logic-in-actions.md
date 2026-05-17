# ADR-0003: No database triggers — all business logic explicit in application code

## Status

Accepted — 2026-05-16

## Context

The original spec proposed PostgreSQL triggers for:

- Automatically recording enrollment transitions (audit trail)
- Awarding XP on state changes
- Promoting waitlisted participants

Triggers are invisible to application code, hard to test, and bypass Laravel's event system.

## Decision

All business logic lives in PHP Action classes. No database triggers. The audit trail (`events_enrollment_transitions`) is written explicitly by the Action that performs the transition.

## Consequences

- **Testable** — every behavior can be tested via Action unit/feature tests without database-level mocking.
- **Visible** — reading an Action tells you everything that happens on a transition (audit write, event dispatch, waitlist promotion).
- **Debuggable** — no hidden side-effects outside of Laravel's control.
- **Trade-off**: a raw SQL update bypassing Actions would skip audit/XP/promotion. Mitigated by never updating enrollment status outside of Actions (enforced by code review, not by the database).
