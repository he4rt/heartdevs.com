# ADR-0005: Bot communicates with Events via domain events, not REST API

## Status

Accepted — 2026-05-16

## Context

Discord/Twitch bots need to trigger check-ins (e.g., user types `!checkin 4829` in chat). Two options:

- (A) Bot calls a REST endpoint on the Events module.
- (B) Bot dispatches a Laravel domain event, Events module listens.

## Decision

Option B — Bot dispatches a domain event (e.g., `CheckInRequested`) containing user identifier, event context, and code. Events module has a listener that validates and processes. Bot is pure transport.

## Consequences

- **Consistent with existing architecture** — Bot Discord already communicates with Moderation via domain events (see CONTEXT-MAP.md).
- **Module boundaries respected** — Bot doesn't import Events classes or know enrollment logic.
- **Testable in isolation** — Events listener can be tested by dispatching the event directly, without HTTP layer.
- **Trade-off**: no synchronous HTTP response to the bot. Bot must listen for a response event (e.g., `CheckInProcessed`) to reply to the user in chat. Adds async complexity but maintains decoupling.
