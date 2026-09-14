# ADR-0001: Enrollment state machine as PHP enum, not spatie/laravel-model-states

## Status

Accepted — 2026-05-16

## Context

The enrollment lifecycle has 8 states with well-defined transitions. We considered `spatie/laravel-model-states` (dedicated state classes, automatic transition validation) vs. a plain PHP enum with a `canTransitionTo()` method and validation in Actions.

## Decision

Use a backed PHP enum (`EnrollmentStatusEnum`) with an explicit `canTransitionTo(self $target): bool` method. Transition side-effects (XP dispatch, waitlist promotion, audit trail) live in Action classes.

## Consequences

- **No extra dependency** — one less package to maintain and version-match.
- **All business logic is explicit** — transitions, guards, and side-effects visible in Action code, not hidden in package config/hooks.
- **Trade-off**: if the state graph grows significantly or requires per-tenant customization, we lose the declarative config that `model-states` provides. Acceptable given the graph is static and small.
- **Testing**: state transitions are tested by calling Actions directly, asserting status + transition records.
