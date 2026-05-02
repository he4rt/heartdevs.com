# Moderation System — Design Spec

## Context

He4rt Developers is a Brazilian developer community with presence on Discord, Twitch, GitHub, Twitter, and a web platform (he4rtdevs.com). Currently, moderation is entirely ad-hoc: each moderator uses manual bans, personal bots, and informal processes with no centralized history, consistency, or tooling.

This system introduces a **platform-agnostic moderation module** that provides a unified pipeline for content classification, a Filament-based moderator dashboard, bidirectional action sync across all platforms, penalty suggestion with escalation guidelines, full appeals workflow, and immutable audit logging.

**Goals:**

- Centralize all moderation decisions in one place regardless of source platform
- Ensure consistency via penalty guidelines and structured decision trees
- Provide full audit trail for compliance and transparency
- Start assistive-only (AI flags, human decides) with path to auto-actions later
- Support any platform via adapter pattern (Discord, Twitch, GitHub, Twitter, Web, future)

**Non-goals (v1):**

- Auto-removal of content (assistive only)
- Real-time stream moderation (Twitch live chat at scale)
- Public moderation transparency reports
- Multi-language content classification

---

## Architecture

### High-Level: B+C Hybrid (Core Module + Platform Adapters + Sync Job Pipeline)

```
PLATFORM ADAPTERS              CORE MODULE                    EXECUTION
(bridge layer)                 (app-modules/moderation/)      (fan-out)

┌──────────┐                   ┌──────────────┐              ┌──────────────┐
│ Discord  │──ingest()───────→│ IngestContent│─────────────→│ ClassifyCont.│
│ Twitch   │                   │ (Job/Sync)   │              │ (Job/Sync)   │
│ GitHub   │                   └──────────────┘              └──────┬───────┘
│ Twitter  │                                                        │
│ Web      │                                                        ▼
└──────────┘                                                 ┌──────────────┐
      ▲                                                      │RouteDecision │
      │                                                      │ (Job/Sync)   │
      │  execute()                                           └──────┬───────┘
      │                                                             │
      │                   ┌──────────────────┐                      │
      └───────────────────│ ExecuteAction    │◄─────────────────────┘
                          │ (Job/Sync)       │      (after moderator decides)
                          │ - fan-out to all │
                          │   user platforms │
                          └──────────────────┘
```

### Principles

1. **Sync-first pipeline:** All jobs use `dispatchSync()`. Switch to async via config when volume demands it.
2. **Platform-agnostic core:** `app-modules/moderation/` imports zero platform-specific code. Communicates via contracts + Laravel events.
3. **Adapter pattern:** Each platform registers its adapter via service container tagging. Core resolves all tagged adapters at runtime.
4. **Assistive moderation:** Pipeline flags and suggests, never auto-executes. Moderator always confirms.
5. **Fan-out execution:** One moderator action propagates to all platforms where the user has presence.

---

## Module Structure

```
app-modules/moderation/
├── composer.json
├── config/moderation.php
├── database/
│   ├── factories/
│   └── migrations/
│       ├── create_moderation_cases_table.php
│       ├── create_moderation_reports_table.php
│       ├── create_moderation_actions_table.php
│       ├── create_moderation_appeals_table.php
│       ├── create_moderation_rules_table.php
│       └── create_moderation_audit_log_table.php
├── routes/
│   └── moderation-routes.php
├── src/
│   ├── ModerationServiceProvider.php
│   ├── Contracts/
│   │   ├── ModerationPlatformContract.php
│   │   ├── ContentClassifierContract.php
│   │   └── PenaltyAdvisorContract.php
│   ├── DTOs/
│   │   ├── ModerationContentDTO.php
│   │   ├── ClassificationResultDTO.php
│   │   ├── SuggestedPenaltyDTO.php
│   │   └── ExecutionResultDTO.php
│   ├── Enums/
│   │   ├── Platform.php
│   │   ├── ActionType.php
│   │   ├── ViolationType.php
│   │   ├── CaseStatus.php
│   │   ├── CaseSource.php
│   │   ├── AppealStatus.php
│   │   └── Severity.php
│   ├── Models/
│   │   ├── ModerationCase.php
│   │   ├── ModerationReport.php
│   │   ├── ModerationAction.php
│   │   ├── ModerationAppeal.php
│   │   ├── ModerationRule.php
│   │   └── ModerationAuditLog.php
│   ├── Jobs/
│   │   ├── IngestContent.php
│   │   ├── ClassifyContent.php
│   │   ├── RouteDecision.php
│   │   └── ExecuteAction.php
│   ├── Actions/
│   │   ├── CreateCase.php
│   │   ├── ResolveCase.php
│   │   ├── AssignCase.php
│   │   ├── EscalateCase.php
│   │   ├── SuggestPenalty.php
│   │   ├── FileAppeal.php
│   │   └── ReviewAppeal.php
│   ├── Classifiers/
│   │   ├── OpenAiClassifier.php
│   │   ├── RuleBasedClassifier.php
│   │   └── AggregateClassifier.php
│   ├── Advisors/
│   │   └── HistoryBasedPenaltyAdvisor.php
│   ├── Events/
│   │   ├── CaseCreated.php
│   │   ├── CaseResolved.php
│   │   └── ActionExecuted.php
│   ├── Listeners/
│   │   └── RecordAuditLog.php
│   └── Filament/
│       └── Admin/
│           ├── Resources/
│           │   ├── ModerationCaseResource.php
│           │   ├── ModerationCaseResource/
│           │   │   ├── Pages/
│           │   │   ├── Schemas/
│           │   │   └── Tables/
│           │   ├── ModerationRuleResource.php
│           │   └── ModerationAppealResource.php
│           ├── Pages/
│           │   └── ModerationDashboard.php
│           └── Widgets/
│               ├── CasesByStatusWidget.php
│               ├── RecentActionsWidget.php
│               └── AppealsSlaWidget.php
└── tests/
    ├── Feature/
    │   ├── PipelineTest.php
    │   ├── CaseWorkflowTest.php
    │   ├── AppealWorkflowTest.php
    │   └── FilamentResourceTest.php
    └── Unit/
        ├── ClassifierTest.php
        ├── PenaltyAdvisorTest.php
        └── DTOTest.php
```

---

## Contracts

### ModerationPlatformContract

```php
interface ModerationPlatformContract
{
    /** Which platform this adapter handles */
    public function platform(): Platform;

    /** Normalize raw platform content into a DTO */
    public function ingest(array $rawPayload): ModerationContentDTO;

    /** Execute a moderation action on this platform */
    public function execute(ModerationAction $action, User $target): ExecutionResultDTO;

    /** Send notification to user on this platform */
    public function notify(User $user, string $message, array $context = []): void;

    /** Which action types this platform supports */
    public function supports(): array;

    /** Resolve internal user from platform-specific identity */
    public function resolveUser(string $externalId): ?User;
}
```

### ContentClassifierContract

```php
interface ContentClassifierContract
{
    /** Classify content and return scores per violation type */
    public function classify(ModerationContentDTO $content): ClassificationResultDTO;

    /** Human-readable name for audit/logging */
    public function name(): string;
}
```

### PenaltyAdvisorContract

```php
interface PenaltyAdvisorContract
{
    /** Suggest penalty based on violation + user history */
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO;
}
```

---

## DTOs

### ModerationContentDTO

```php
final readonly class ModerationContentDTO
{
    public function __construct(
        public string $contentId,
        public string $contentType, // 'message', 'post', 'profile', 'image'
        public Platform $sourcePlatform,
        public string $authorExternalId,
        public ?User $author,
        public string $textContent,
        public array $mediaUrls, // images, videos, attachments
        public array $metadata, // channel, guild, thread, etc.
        public array $snapshot, // full raw content preserved
        public ?string $tenantId,
    ) {}
}
```

### ClassificationResultDTO

```php
final readonly class ClassificationResultDTO
{
    public function __construct(
        public array $scores, // ['spam' => 0.92, 'toxicity' => 0.45, ...]
        public ?ViolationType $primary, // highest scoring violation (if above threshold)
        public ?Severity $severity,
        public string $classifierName,
        public array $matchedRules,
    ) {
        // rule IDs that triggered
    }
}
```

### SuggestedPenaltyDTO

```php
final readonly class SuggestedPenaltyDTO
{
    public function __construct(
        public ActionType $action,
        public ?string $duration, // '7d', '30d', 'permanent', null for warn
        public string $reasoning, // "3rd offense in 30 days, previous: 2 warns"
        public int $priorOffenses,
        public array $history,
    ) {
        // summary of past actions on this user
    }
}
```

### ExecutionResultDTO

```php
final readonly class ExecutionResultDTO
{
    public function __construct(
        public Platform $platform,
        public bool $success,
        public ?string $error,
        public array $platformResponse,
    ) {
        // raw API response for debugging
    }
}
```

---

## Enums

```php
enum Platform: string
{
    case Discord = 'discord';
    case Twitch = 'twitch';
    case GitHub = 'github';
    case Twitter = 'twitter';
    case Web = 'web';
}

enum ActionType: string
{
    case Warn = 'warn';
    case Mute = 'mute';
    case Kick = 'kick';
    case Ban = 'ban';
    case Suspend = 'suspend';
    case ContentRemove = 'content_remove';
}

enum ViolationType: string
{
    case Spam = 'spam';
    case Toxicity = 'toxicity';
    case Harassment = 'harassment';
    case Nsfw = 'nsfw';
    case Raid = 'raid';
    case Impersonation = 'impersonation';
    case Other = 'other';
}

enum CaseStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Dismissed = 'dismissed';
}

enum CaseSource: string
{
    case UserReport = 'user_report';
    case AutoDetect = 'auto_detect';
    case RuleMatch = 'rule_match';
    case ManualFlag = 'manual_flag';
}

enum AppealStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Upheld = 'upheld';
    case Overturned = 'overturned';
}

enum Severity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
```

---

## Database Schema

### moderation_cases

| Column             | Type            | Notes                                 |
| ------------------ | --------------- | ------------------------------------- |
| id                 | uuid PK         |                                       |
| content_type       | varchar(50)     | 'message', 'post', 'profile', 'image' |
| content_id         | varchar(255)    | external or internal ID               |
| content_snapshot   | jsonb           | preserved evidence                    |
| source_platform    | varchar(20)     | Platform enum value                   |
| source             | varchar(20)     | CaseSource enum                       |
| status             | varchar(20)     | CaseStatus enum                       |
| priority           | integer         | 0-100, dynamic                        |
| severity           | varchar(20)     | Severity enum                         |
| violation_type     | varchar(30)     | ViolationType enum                    |
| ai_scores          | jsonb           | {"spam": 0.92, "toxicity": 0.12}      |
| classifier_version | varchar(50)     | which model/version produced scores   |
| suggested_action   | varchar(30)     | ActionType enum (system suggestion)   |
| assigned_to        | uuid FK users   | current moderator                     |
| assigned_at        | timestamptz     |                                       |
| resolved_at        | timestamptz     |                                       |
| author_id          | uuid FK users   | who created the content               |
| tenant_id          | uuid FK tenants |                                       |
| created_at         | timestamptz     |                                       |
| updated_at         | timestamptz     |                                       |

**Indexes:**

- `(tenant_id, status, priority DESC, created_at)` WHERE status IN ('pending','assigned') — queue fetch
- `(author_id, created_at DESC)` — user history lookup
- `(source_platform, created_at DESC)` — per-platform analytics

### moderation_reports

| Column      | Type                     | Notes                       |
| ----------- | ------------------------ | --------------------------- |
| id          | uuid PK                  |                             |
| case_id     | uuid FK moderation_cases | groups reports to same case |
| reporter_id | uuid FK users            |                             |
| reason      | varchar(30)              | ViolationType enum          |
| details     | text                     | free-form description       |
| platform    | varchar(20)              | where report was submitted  |
| created_at  | timestamptz              |                             |

### moderation_actions

| Column            | Type                     | Notes                                                              |
| ----------------- | ------------------------ | ------------------------------------------------------------------ |
| id                | uuid PK                  |                                                                    |
| case_id           | uuid FK moderation_cases |                                                                    |
| moderator_id      | uuid FK users            | NULL if system-generated                                           |
| action_type       | varchar(30)              | ActionType enum                                                    |
| target_platforms  | jsonb                    | ['discord','twitch','web']                                         |
| duration          | varchar(20)              | '7d', '30d', 'permanent', null                                     |
| reason            | text                     | moderator's stated reason                                          |
| metadata          | jsonb                    | extra context                                                      |
| execution_results | jsonb                    | {discord: {success: true}, twitch: {success: false, error: "..."}} |
| automated         | boolean                  | false (v1 always false)                                            |
| created_at        | timestamptz              |                                                                    |

### moderation_appeals

| Column          | Type                       | Notes                               |
| --------------- | -------------------------- | ----------------------------------- |
| id              | uuid PK                    |                                     |
| action_id       | uuid FK moderation_actions | which action is being contested     |
| appellant_id    | uuid FK users              |                                     |
| reason_category | varchar(50)                |                                     |
| reason_text     | text                       |                                     |
| status          | varchar(20)                | AppealStatus enum                   |
| reviewer_id     | uuid FK users              | MUST differ from original moderator |
| reviewer_notes  | text                       |                                     |
| resolved_at     | timestamptz                |                                     |
| sla_deadline    | timestamptz                | created_at + 48h                    |
| created_at      | timestamptz                |                                     |

**Constraint:** reviewer_id != moderation_actions.moderator_id (enforced at application layer)

### moderation_rules

| Column          | Type                 | Notes                           |
| --------------- | -------------------- | ------------------------------- |
| id              | uuid PK              |                                 |
| name            | varchar(100)         | human-readable rule name        |
| type            | varchar(20)          | 'keyword', 'regex', 'threshold' |
| platform        | varchar(20) nullable | NULL = all platforms            |
| pattern         | text                 | the regex/keyword/config        |
| violation_type  | varchar(30)          | what it detects                 |
| severity        | varchar(20)          |                                 |
| action_on_match | varchar(30)          | ActionType to suggest           |
| is_active       | boolean              | hot-toggle without deploy       |
| tenant_id       | uuid FK tenants      |                                 |
| created_at      | timestamptz          |                                 |
| updated_at      | timestamptz          |                                 |

### moderation_audit_log (partitioned by month)

| Column     | Type        | Notes                                                |
| ---------- | ----------- | ---------------------------------------------------- |
| id         | bigserial   |                                                      |
| event_type | varchar(50) | 'case_created', 'action_taken', 'appeal_filed', etc. |
| actor_id   | uuid        | who did it                                           |
| actor_type | varchar(20) | 'moderator', 'system', 'user'                        |
| case_id    | uuid        | related case                                         |
| details    | jsonb       | full event payload                                   |
| platform   | varchar(20) |                                                      |
| tenant_id  | uuid        |                                                      |
| created_at | timestamptz | partition key                                        |

---

## Pipeline Jobs

### IngestContent

**Input:** Raw payload from any platform adapter + platform identifier
**Output:** `ModerationCase` created with status `pending`, snapshot preserved

```php
final class IngestContent implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ModerationContentDTO $content, private readonly CaseSource $source) {}

    public function handle(CreateCase $createCase): ModerationCase
    {
        return $createCase->handle($this->content, $this->source);
    }
}
```

### ClassifyContent

**Input:** `ModerationCase`
**Output:** Case updated with `ai_scores`, `violation_type`, `severity`

Runs all registered `ContentClassifierContract` implementations (OpenAI + Rules), aggregates scores.

### RouteDecision

**Input:** `ModerationCase` with scores
**Output:** Case routed: priority set, `suggested_action` populated, placed in queue

Logic:

- Score >= config threshold → high priority + suggested action
- Multiple reports on same content → priority boost (+10 per report)
- User has prior offenses → severity bump
- All scores below threshold → status = `dismissed` (logged but no action needed)

### ExecuteAction

**Input:** `ModerationAction` (created by moderator via Filament)
**Output:** Fan-out to all target platforms, results recorded

```php
final class ExecuteAction implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ModerationAction $action, private readonly User $target) {}

    public function handle(): void
    {
        $platforms = app()->tagged('moderation.platforms');
        $results = [];

        foreach ($platforms as $adapter) {
            if (in_array($adapter->platform()->value, $this->action->target_platforms)) {
                $results[] = $adapter->execute($this->action, $this->target);
            }
        }

        $this->action->update(['execution_results' => $results]);
        CaseResolved::dispatch($this->action->case);
    }
}
```

---

## Penalty Advisor

The `HistoryBasedPenaltyAdvisor` suggests penalties based on:

| Prior Offenses (30d) | Violation Severity | Suggestion    |
| -------------------- | ------------------ | ------------- |
| 0                    | low                | warn          |
| 0                    | medium             | warn          |
| 0                    | high/critical      | mute 24h      |
| 1                    | low                | warn          |
| 1                    | medium             | mute 24h      |
| 1                    | high/critical      | mute 7d       |
| 2                    | any                | ban 7d        |
| 3+                   | any                | ban 30d       |
| 5+                   | any                | ban permanent |

Moderator sees the suggestion + reasoning but ALWAYS makes the final call. The suggestion includes history summary: "User has 2 prior warns in last 30 days (spam on May 3, toxicity on May 15)."

---

## Filament Admin Panel

### ModerationDashboard (Page)

Widgets:

- **Cases by Status** — donut chart (pending/assigned/resolved/dismissed)
- **Recent Actions** — latest 10 actions taken with moderator name
- **Appeals SLA** — appeals approaching/exceeding 48h deadline
- **Platform Distribution** — bar chart of cases by source platform

### ModerationCaseResource

**List view:** Filterable queue sorted by priority DESC. Columns: priority badge, violation type, platform icon, author, snippet, AI score, status, assigned_to, created_at.

**View/Edit:** Full case detail with:

- Content snapshot (text + media preview)
- AI scores breakdown (visual bars)
- Reports list (who reported, reason)
- Suggested action (highlighted)
- User history sidebar (prior cases, total offenses)
- Action form: select action type, duration, target platforms (checkboxes), reason text
- Execute button triggers `ExecuteAction` job

### ModerationRuleResource

CRUD for moderation rules. Toggle active/inactive. Test rule against sample content inline.

### ModerationAppealResource

List of pending appeals with SLA countdown. Assign to reviewer (system prevents assigning to original moderator). Review form: uphold/overturn + notes.

---

## Platform Adapter Registration

Each module that provides a platform adapter registers it via service container tagging:

```php
// In BotDiscordServiceProvider (or dedicated DiscordModerationServiceProvider)
public function register(): void
{
    $this->app->singleton(DiscordModerationAdapter::class);
    $this->app->tag([DiscordModerationAdapter::class], 'moderation.platforms');
}

// In ModerationServiceProvider — resolves all at runtime
public function boot(): void
{
    // Pipeline and Filament can resolve:
    // $adapters = app()->tagged('moderation.platforms');
}
```

Adding a new platform = create adapter class + tag it. Zero changes to core module.

---

## Discord Adapter (Reference Implementation)

Located in `app-modules/bot-discord/src/Adapters/DiscordModerationAdapter.php`:

```php
final class DiscordModerationAdapter implements ModerationPlatformContract
{
    public function platform(): Platform
    {
        return Platform::Discord;
    }

    public function supports(): array
    {
        return [ActionType::Warn, ActionType::Mute, ActionType::Kick, ActionType::Ban];
    }

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        $discordId = $target->externalIdentity(Platform::Discord)?->external_account_id;

        // Map ActionType to Discord API call
        // Ban → PUT /guilds/{id}/bans/{user_id}
        // Mute → PATCH /guilds/{id}/members/{user_id} communication_disabled_until
        // Kick → DELETE /guilds/{id}/members/{user_id}
        // Warn → DM only (no Discord API equivalent)
    }

    public function ingest(array $rawPayload): ModerationContentDTO
    {
        // Normalize Discord MESSAGE_CREATE event into DTO
        // Extract: content, attachments, author, guild_id, channel_id
    }
}
```

---

## Web Adapter

Located in `app-modules/moderation/src/Adapters/WebModerationAdapter.php` (lives in core since web is internal):

```php
final class WebModerationAdapter implements ModerationPlatformContract
{
    public function platform(): Platform
    {
        return Platform::Web;
    }

    public function supports(): array
    {
        return [ActionType::Warn, ActionType::Suspend, ActionType::Ban, ActionType::ContentRemove];
    }

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        // Suspend → set suspended_until on user
        // Ban → set banned_at on user
        // ContentRemove → soft-delete the content
        // Warn → create notification record
    }
}
```

---

## Events & Listeners

| Event            | Dispatched When               | Listeners                          |
| ---------------- | ----------------------------- | ---------------------------------- |
| `CaseCreated`    | After IngestContent completes | RecordAuditLog                     |
| `CaseResolved`   | After moderator resolves case | RecordAuditLog, UpdateMetrics      |
| `ActionExecuted` | After ExecuteAction fan-out   | RecordAuditLog, NotifyAppealWindow |

All listeners are sync (same transaction). Audit log is always written.

---

## Configuration

```php
// config/moderation.php
return [
    'pipeline' => [
        'sync' => env('MODERATION_PIPELINE_SYNC', true), // true = dispatchSync, false = dispatch
        'queue' => env('MODERATION_QUEUE', 'moderation'),
    ],

    'classifiers' => [
        'openai' => [
            'enabled' => env('MODERATION_OPENAI_ENABLED', true),
            'model' => 'omni-moderation-latest',
        ],
        'rules' => [
            'enabled' => true, // always enabled
        ],
    ],

    'thresholds' => [
        'flag' => 0.7, // score >= 0.7 → flagged for review
        'high_priority' => 0.9, // score >= 0.9 → high priority in queue
        'dismiss' => 0.3, // all scores < 0.3 → auto-dismiss (logged)
    ],

    'penalties' => [
        'escalation_window_days' => 30, // look at last 30 days for history
    ],

    'appeals' => [
        'sla_hours' => 48,
        'window_days' => 7, // user has 7 days to file appeal after action
    ],

    'platforms' => [
        // Adapters auto-discovered via container tagging
        // Manual overrides per-platform here if needed
    ],
];
```

---

## User Flows

### Report Flow (User → Pipeline → Queue)

```
 USER                              SYSTEM
  │                                   │
  │  👆 "Report" (Discord reaction    │
  │     OR web button OR /report cmd) │
  │ ──────────────────────────────►  │
  │                                   │  PlatformAdapter::ingest()
  │                                   │  → ModerationContentDTO
  │                                   │
  │                                   │  dispatchSync(IngestContent)
  │                                   │  → ModerationCase created
  │                                   │
  │                                   │  dispatchSync(ClassifyContent)
  │                                   │  → ai_scores: {spam: 0.87}
  │                                   │
  │                                   │  dispatchSync(RouteDecision)
  │                                   │  → priority: 80, suggested: warn
  │                                   │  → status: pending
  │                                   │
  │    "Report received. Our team     │
  │     will review shortly."         │
  │ ◄────────────────────────────────│
  │                                   │
```

### Moderator Flow (Queue → Decision → Execution)

```
 MODERADOR                           SYSTEM
  │                                   │
  │  👆 abre Filament /admin          │
  │     ModerationCaseResource        │
  │ ──────────────────────────────►  │
  │                                   │  Query: status=pending
  │                                   │  ORDER BY priority DESC
  │                                   │
  │    ┌──────────────────────────┐   │
  │    │ ⚠️ HIGH | spam: 0.87     │   │
  │    │ Platform: Discord 🎮     │   │
  │    │ Author: @user123         │   │
  │    │ "Buy cheap followers..." │   │
  │    │                          │   │
  │    │ 💡 Suggested: Warn       │   │
  │    │ (0 prior offenses)       │   │
  │    │                          │   │
  │    │ ┌──────┐ ┌────┐ ┌─────┐ │   │
  │    │ │ Warn │ │Mute│ │ Ban │ │   │
  │    │ └──────┘ └────┘ └─────┘ │   │
  │    │                          │   │
  │    │ Target: ☑Discord ☑Web    │   │
  │    │ Duration: [7 days    ▼]  │   │
  │    │ Reason: [____________]   │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 selects "Ban", 7d,            │
  │     platforms: Discord + Web      │
  │ ──────────────────────────────►  │
  │                                   │  ModerationAction created
  │                                   │  dispatchSync(ExecuteAction)
  │                                   │  │
  │                                   │  ├─ DiscordAdapter::execute(ban, 7d) ✓
  │                                   │  ├─ WebAdapter::execute(suspend, 7d) ✓
  │                                   │  └─ AuditLog recorded
  │                                   │
  │    "Action executed:              │
  │     Discord ✓ Web ✓               │
  │     User notified."               │
  │ ◄────────────────────────────────│
  │                                   │
```

### Appeal Flow

```
 USER                              SYSTEM
  │                                   │
  │  👆 "Contest this decision"       │
  │     (web panel or Discord DM)     │
  │ ──────────────────────────────►  │
  │                                   │  Validate: within 7-day window? ✓
  │                                   │  Validate: action has no prior appeal? ✓
  │                                   │
  │    "Select reason:"               │
  │    ┌──────────────────────────┐   │
  │    │ Context misunderstood    │   │
  │    │ Wrong person             │   │
  │    │ Disproportionate penalty │   │
  │    │ Other                    │   │
  │    └──────────────────────────┘   │
  │                                   │
  │  👆 "Context misunderstood"       │
  │  ⌨️ "I was being sarcastic..."    │
  │ ──────────────────────────────►  │
  │                                   │  ModerationAppeal created
  │                                   │  sla_deadline: NOW() + 48h
  │                                   │  Assign reviewer (≠ original mod)
  │                                   │  AuditLog: appeal_filed
  │                                   │
  │    "Appeal submitted.             │
  │     A different moderator will    │
  │     review within 48 hours."      │
  │ ◄────────────────────────────────│
  │                                   │

 REVIEWER                            SYSTEM
  │                                   │
  │  👆 opens AppealResource          │
  │ ──────────────────────────────►  │
  │                                   │  Shows: original content, action,
  │                                   │  user's appeal reason, full history
  │                                   │
  │  👆 "Overturn" + notes            │
  │ ──────────────────────────────►  │
  │                                   │  Appeal: status = overturned
  │                                   │  Reverse action on all platforms
  │                                   │  (unban Discord, unsuspend Web)
  │                                   │  Notify user: "Appeal accepted"
  │                                   │  AuditLog: appeal_overturned
  │                                   │
```

---

## Testing Strategy

### Unit Tests

- **ClassifierTest:** Mock OpenAI API responses, verify score aggregation
- **PenaltyAdvisorTest:** Given N prior offenses + severity → correct suggestion
- **DTOTest:** Verify serialization/deserialization of all DTOs

### Feature Tests

- **PipelineTest:** Full IngestContent → ClassifyContent → RouteDecision flow with mocked classifier
- **CaseWorkflowTest:** Create case → assign → resolve → verify state transitions
- **AppealWorkflowTest:** File appeal → assign reviewer (≠ original mod) → overturn → verify reversal
- **FilamentResourceTest:** Filament page renders, actions work, filters apply
- **AdapterTest:** Each platform adapter correctly maps ActionTypes to platform-specific calls (mocked HTTP)

### Integration Tests

- **ExecuteAction with multiple adapters:** Verify fan-out, partial failure handling (one platform fails, others succeed)
- **Report deduplication:** Multiple reports on same content consolidate into one case with priority boost

---

## Verification Plan

1. Run `php artisan test --filter=Moderation` — all tests pass
2. Run `vendor/bin/pint --dirty --format agent` — code style clean
3. Run `php artisan migrate` — migrations apply without error
4. Open Filament `/admin` → verify ModerationDashboard, CaseResource, RuleResource, AppealResource load
5. Manually create a ModerationCase via tinker → verify it appears in Filament queue
6. Simulate full pipeline: create case → classify (mock) → route → resolve via Filament → verify audit log entry
7. Test appeal flow: create action → file appeal → assign different reviewer → overturn → verify reversal

---

## Future Iterations (Out of Scope v1)

- **Auto-actions:** Flip `MODERATION_PIPELINE_SYNC=false`, add confidence threshold for auto-removal
- **Real-time Twitch chat:** Dedicated high-throughput adapter with sampling
- **ML model training:** Use moderation decisions as labeled data to train custom classifiers
- **Transparency reports:** Public-facing quarterly moderation stats
- **Community mod tools:** Give server mods limited self-service moderation via Discord commands
- **Webhook ingestion:** Generic webhook endpoint for arbitrary platform integrations
