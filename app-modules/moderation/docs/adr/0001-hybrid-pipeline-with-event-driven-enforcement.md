# ADR-0001: Hybrid Pipeline with Event-Driven Enforcement

**Status:** Accepted  
**Date:** 2026-05-16  
**Deciders:** danielhe4rt

## Context

The moderation system was initially built as a single `MessageReceivedEvent` handler in the `bot-discord` module that performed the entire pipeline inline: ingestion, classification (rules + AI), case creation, routing, and enforcement. This approach:

1. **Blocked the Discord websocket event loop** — AI classification takes 1-3s, stalling all other bot events.
2. **Made the bot-discord module know every internal detail** of the moderation domain (classifiers, rules, penalty advisors).
3. **Was untestable in isolation** — testing classification required mocking the entire Discord message context.
4. **Was not extensible** — adding Twitch or Telegram would require duplicating the entire orchestration.

We need an architecture that:

- Keeps the Discord event loop non-blocking
- Respects module boundaries (moderation is platform-agnostic)
- Supports multiple platforms with minimal per-platform code
- Allows retry/backoff for AI calls without duplicating work
- Lets the moderation domain own its policies (when to auto-execute, escalation rules)

## Decision

We adopt a **hybrid architecture** with three key properties:

### 1. Synchronous pre-screening, async classification (C2 pattern)

`SubmitForModeration` is the single entry point. It runs `RuleBasedClassifier` synchronously (<5ms, regex on DB) to catch obvious violations immediately. For non-matching content, it dispatches `ScreenContent` to the queue for AI evaluation. This avoids creating cases for the ~99% of messages that are safe.

### 2. Event-driven enforcement

The moderation module emits `CaseReadyForEnforcement` only when its auto-execution policy is satisfied (deterministic rule match + suggested action exists). Platform modules listen to this event and handle execution. The moderation module never imports platform-specific code.

### 3. Platform adapters resolve and execute, domain decides policy

- **Moderation decides**: what action to take, when auto-execution is safe, penalty escalation
- **Platform adapters decide**: how to execute (Discord API calls, role-based protection/immunity)
- **Transport layer handles**: HTTP communication (Saloon connectors/requests in `integration-discord`)

### Key structural decisions within this ADR:

| Aspect                   | Decision                                                                            |
| ------------------------ | ----------------------------------------------------------------------------------- |
| Pre-screening            | Rules-only sync, AI goes to queue (`ScreenContent` job)                             |
| Case creation            | Only when pre-screen flags (rule match) OR async AI exceeds threshold               |
| Classification + Routing | Single job (`ClassifyAndRoute`), routing extracted as `RouteCaseAction`             |
| Auto-execution policy    | Owned by moderation, emits event only when safe                                     |
| Enforcement trigger      | Domain event `CaseReadyForEnforcement` → platform listener                          |
| Adapter registration     | `PlatformRegistry` singleton with `Platform` enum lookup                            |
| Identity resolution      | Delegated to `FindExternalIdentity` action from identity module                     |
| HTTP transport           | Saloon connectors in `integration-discord/Transport/`                               |
| DTO design               | `ModerationContentDTO` carries only `authorExternalId` (string), no Eloquent models |
| Protection/immunity      | `DiscordRoleResolver` in integration-discord, consumed by adapter                   |

## Alternatives Considered

### A — Synchronous facade (`ModerationPipeline::process()`)

Single method orchestrates everything. Simple but blocks the event loop (AI takes 1-3s) and provides no retry granularity.

### B — Full job chain (`Bus::chain([Ingest, Classify, Route, Execute])`)

Maximum async granularity but `Bus::chain` doesn't pass data between jobs easily, and enforcement decision is coupled inside `RouteDecision`. No event-driven hook for platforms.

## Consequences

### Positive

- Discord event loop stays responsive (only a <5ms regex check runs sync)
- Adding a new platform = implement `ModerationPlatformContract` + register in `PlatformRegistry` + add listener for `CaseReadyForEnforcement`
- AI failures retry without re-running rules or creating duplicate cases
- Each module is testable in isolation (mock `DiscordConnector` with Saloon `MockClient`)
- `moderation_cases` table stays clean — only flagged content gets a case

### Negative

- More indirection to follow (event → listener → job) — requires Horizon for debugging
- Two classification paths (rule-match vs. AI-screen) that must converge correctly
- `PlatformRegistry` is an extra moving part vs. simple container tags

### Risks

- If `ScreenContent` queue backs up, AI moderation has latency. Mitigated by: rules catch the worst offenders instantly; AI is for borderline cases that can tolerate seconds of delay.
- Stale `FindExternalIdentity` cache (2 days) could miss a recently linked account. Acceptable: user links account → next message is already cached.
