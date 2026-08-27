# ADR-0002: XP awarded via domain events, not an in-module ledger

## Status

Accepted — 2026-05-16

## Context

We considered three approaches for XP integration:

- (A) Events dispatches domain events, Gamification listens and increments `Character.experience`.
- (B) Events maintains its own `events_xp_rewards` ledger and calls Gamification.
- (C) Centralized ledger in Gamification with `source_type`/`source_id`.

## Decision

Option A — fire-and-forget domain events. Events module publishes `EnrollmentConfirmed`, `ParticipantCheckedIn`, `ParticipantAttended` with XP amounts from the enrollment policy. Gamification module subscribes and increments.

## Consequences

- **Module boundary respected** — Events has zero knowledge of how XP is stored or calculated.
- **No ledger in Events** — auditability comes from the `events_enrollment_transitions` table (which records every state change) + Gamification's own records.
- **Trade-off**: no single "XP history per event" query without joining across modules. Acceptable for MVP; a cross-module read model can be added later if needed.
- **Idempotency**: the listener must be idempotent (check if XP was already granted for this enrollment+reason). This responsibility moves to Gamification.
