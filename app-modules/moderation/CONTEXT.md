# Moderation Context

Platform-agnostic content moderation domain. Responsible for classifying content, routing decisions, suggesting and enforcing penalties, and managing appeals.

## Glossary

| Term                   | Definition                                                                                                                                                                                                     | Not to be confused with                                          |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| **Case**               | A record representing a piece of content flagged for review. Has a lifecycle: `screening → pending → resolved/dismissed`.                                                                                      | A "report" (which is user-submitted evidence attached to a case) |
| **Classification**     | The process of determining violation type, severity, and confidence scores for a piece of content. Can be rule-based (deterministic) or AI-powered (probabilistic).                                            | —                                                                |
| **Pre-screening**      | A fast, synchronous check using only rules (regex/keyword). Runs inline before any case is created. Filters out the ~99% of content that is clearly safe.                                                      | Full classification (which includes AI)                          |
| **Routing**            | The process of assigning priority and suggesting an action for a classified case, based on severity, AI scores, report count, and the author's offense history.                                                | —                                                                |
| **Enforcement**        | Executing a moderation action (mute, kick, ban, warn, content removal) on a target platform.                                                                                                                   | "Routing" (which decides what to do; enforcement does it)        |
| **Penalty Escalation** | Increasing the severity of punishment based on prior offenses within a configurable window (default: 30 days).                                                                                                 | —                                                                |
| **Platform Adapter**   | An implementation of `ModerationPlatformContract` that knows how to execute actions on a specific platform (Discord, Web, Twitch, etc.). Registered in the `PlatformRegistry`.                                 | The transport layer (which is just HTTP communication)           |
| **Auto-execution**     | Automatically enforcing an action without human review. Only allowed when classification is deterministic (rule-based, `classifier_version='rules'`). AI-only classifications always go to human review queue. | —                                                                |
| **Protection Tier**    | A platform-specific concept where certain users (admins, moderators) are immune to punitive actions or require elevated permissions to punish. Resolved by the platform adapter.                               | Authorization/RBAC (which is about panel access)                 |
| **Appeal**             | A request by a punished user to review and potentially overturn a moderation action. Has SLA (48h) and submission window (7 days).                                                                             | —                                                                |
| **Case Source**        | How the case originated: `auto_detect` (pipeline caught it), `user_report` (community member reported), `manual` (moderator created).                                                                          | —                                                                |
| **Platform Registry**  | A singleton that maps `Platform` enum values to their corresponding adapter instances. Allows O(1) lookup instead of iterating all registered adapters.                                                        | Container tags                                                   |

## Pipeline Flow

```
Content arrives (from any platform)
       │
       ▼
┌─────────────────────┐
│ SubmitForModeration  │  (Action — entry point)
│                      │
│ 1. Pre-screen (sync) │──── RuleBasedClassifier
│    rules only, <5ms  │
└──────────┬───────────┘
           │
     ┌─────┴──────┐
     │             │
  rule match    no match
     │             │
     ▼             ▼
┌─────────┐  ┌──────────────┐
│ Create  │  │ ScreenContent │ (Job — async)
���  Case   │  │              │
│    +    │  │ Calls AI     │
│ dispatch│  │ If flagged:  │
│ Classify│  │  create case │
│ AndRoute│  │  + route     │
└────┬────┘  └──────┬───────┘
     │               │
     ▼               ▼
┌────────────────────────┐
│    ClassifyAndRoute    │ (Job — async, only for rule-match path)
│                        │
│ 1. Classify (AI enrich)│
│ 2. RouteCaseAction     │
│    - priority calc     │
│    - penalty advisor   ��
│ 3. Emit CaseQueued     │
│ 4. If auto-executable: │
│    emit CaseReady...   │
└────────────────────────┘
           │
           ▼
┌────────────────────────────┐
│ CaseReadyForEnforcement    ��� (Domain Event)
│                            │
│ Listeners (per platform):  │
│  → AutoExecuteAction       │
│    creates ModerationAction│
│    dispatches ExecuteAction │
└────────────────────────────┘
```

## Domain Events

| Event                     | Emitted when                                                         | Consumers                                         |
| ------------------------- | -------------------------------------------------------------------- | ------------------------------------------------- |
| `CaseCreated`             | A new case is persisted                                              | Audit log                                         |
| `CaseQueued`              | Case is classified and queued for review                             | Platform notification (e.g., Discord mod channel) |
| `CaseReadyForEnforcement` | Case passes auto-execution policy (deterministic + suggested action) | Platform listeners that execute the action        |
| `CaseResolved`            | Case reaches terminal state                                          | Audit log                                         |
| `ActionExecuted`          | Enforcement completed on target platform                             | Audit log                                         |

## Module Boundaries

### This module owns:

- Classification logic (rules, AI, aggregate)
- Penalty escalation policy
- Auto-execution policy ("when is it safe to auto-execute?")
- Case lifecycle (pending → resolved/dismissed)
- Appeal workflow
- Audit logging
- `ModerationPlatformContract` definition
- `PlatformRegistry`
- Domain events

### This module does NOT own:

- Platform-specific HTTP communication (belongs to `integration-*`)
- Discord embed formatting (belongs to `bot-discord`)
- Identity resolution (belongs to `identity` — consumed via `FindExternalIdentity`)
- Webhook/websocket runtime (belongs to `bot-discord`)

## Configuration

Key config values in `config/moderation.php`:

- `thresholds.flag` (0.7) — minimum AI score to create a case
- `thresholds.high_priority` (0.9) — score that boosts priority
- `penalties.escalation_window_days` (30) — lookback for prior offenses
- `classifiers.openai.enabled` — toggle AI classification
- `pipeline.queue` — queue name for moderation jobs
