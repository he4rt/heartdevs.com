# Moderation System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a platform-agnostic moderation module for He4rt Developers with sync job pipeline, Filament admin panel, penalty advisor, appeals workflow, and multi-platform action fan-out.

**Architecture:** Core module (`app-modules/moderation/`) owns all moderation logic. Platform adapters implement `ModerationPlatformContract` and register via container tagging. Pipeline uses sync jobs (`dispatchSync`) with config toggle to async. Filament resources auto-discovered via `discoverResourcesForPanel`.

**Tech Stack:** PHP 8.5, Laravel 12, Filament v5, Pest 4, PostgreSQL, OpenAI Moderation API, internachi/modular

**Specs:**

- Design: `docs/superpowers/specs/2026-05-01-moderation-system-design.md`
- Use Cases: `docs/superpowers/specs/2026-05-01-moderation-use-cases.md`

---

## Task 1: Module Scaffold

**Files:**

- Create: `app-modules/moderation/composer.json`
- Create: `app-modules/moderation/config/moderation.php`
- Create: `app-modules/moderation/src/ModerationServiceProvider.php`
- Create: `app-modules/moderation/database/migrations/.gitkeep`
- Create: `app-modules/moderation/database/factories/.gitkeep`
- Create: `app-modules/moderation/routes/moderation-routes.php`
- Create: `app-modules/moderation/tests/Feature/.gitkeep`
- Create: `app-modules/moderation/tests/Unit/.gitkeep`
- Modify: `composer.json` (root) — add module to repositories/autoload

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "he4rt/moderation",
    "description": "Platform-agnostic moderation system",
    "type": "library",
    "version": "1.0",
    "license": "proprietary",
    "require": {},
    "autoload": {
        "psr-4": {
            "He4rt\\Moderation\\": "src/",
            "He4rt\\Moderation\\Database\\Factories\\": "database/factories/",
            "He4rt\\Moderation\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "He4rt\\Moderation\\Tests\\": "tests/"
        }
    },
    "minimum-stability": "stable",
    "extra": {
        "laravel": {
            "providers": ["He4rt\\Moderation\\ModerationServiceProvider"]
        }
    }
}
```

- [ ] **Step 2: Create config/moderation.php**

```php
<?php

declare(strict_types=1);

return [
    'pipeline' => [
        'sync' => env('MODERATION_PIPELINE_SYNC', true),
        'queue' => env('MODERATION_QUEUE', 'moderation'),
    ],

    'classifiers' => [
        'openai' => [
            'enabled' => env('MODERATION_OPENAI_ENABLED', true),
            'model' => 'omni-moderation-latest',
        ],
        'rules' => [
            'enabled' => true,
        ],
    ],

    'thresholds' => [
        'flag' => 0.7,
        'high_priority' => 0.9,
        'dismiss' => 0.3,
    ],

    'penalties' => [
        'escalation_window_days' => 30,
    ],

    'appeals' => [
        'sla_hours' => 48,
        'window_days' => 7,
    ],
];
```

- [ ] **Step 3: Create ModerationServiceProvider**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation;

use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moderation.php', 'moderation');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

- [ ] **Step 4: Create routes file**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Moderation API routes will be added here as needed
```

- [ ] **Step 5: Create .gitkeep files for empty directories**

Create empty `.gitkeep` in:

- `app-modules/moderation/database/migrations/`
- `app-modules/moderation/database/factories/`
- `app-modules/moderation/tests/Feature/`
- `app-modules/moderation/tests/Unit/`

- [ ] **Step 6: Add module to root composer.json**

Add `"He4rt\\Moderation\\": "app-modules/moderation/src/"` to `autoload.psr-4` and `"He4rt\\Moderation\\Tests\\": "app-modules/moderation/tests/"` to `autoload-dev.psr-4`.

- [ ] **Step 7: Run composer dump-autoload and verify**

Run: `composer dump-autoload`
Expected: No errors

- [ ] **Step 8: Commit**

```bash
git add app-modules/moderation/ composer.json
git commit -m "feat(moderation): scaffold module with config and service provider"
```

---

## Task 2: Enums

**Files:**

- Create: `app-modules/moderation/src/Enums/Platform.php`
- Create: `app-modules/moderation/src/Enums/ActionType.php`
- Create: `app-modules/moderation/src/Enums/ViolationType.php`
- Create: `app-modules/moderation/src/Enums/CaseStatus.php`
- Create: `app-modules/moderation/src/Enums/CaseSource.php`
- Create: `app-modules/moderation/src/Enums/AppealStatus.php`
- Create: `app-modules/moderation/src/Enums/Severity.php`
- Test: `app-modules/moderation/tests/Unit/EnumTest.php`

- [ ] **Step 1: Write enum test**

```php
<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

test('Platform enum has all expected cases', function (): void {
    expect(Platform::cases())
        ->toHaveCount(5)
        ->and(Platform::Discord->value)
        ->toBe('discord')
        ->and(Platform::Twitch->value)
        ->toBe('twitch')
        ->and(Platform::GitHub->value)
        ->toBe('github')
        ->and(Platform::Twitter->value)
        ->toBe('twitter')
        ->and(Platform::Web->value)
        ->toBe('web');
});

test('ActionType enum has all expected cases', function (): void {
    expect(ActionType::cases())
        ->toHaveCount(6)
        ->and(ActionType::Warn->value)
        ->toBe('warn')
        ->and(ActionType::Mute->value)
        ->toBe('mute')
        ->and(ActionType::Kick->value)
        ->toBe('kick')
        ->and(ActionType::Ban->value)
        ->toBe('ban')
        ->and(ActionType::Suspend->value)
        ->toBe('suspend')
        ->and(ActionType::ContentRemove->value)
        ->toBe('content_remove');
});

test('ViolationType enum has all expected cases', function (): void {
    expect(ViolationType::cases())
        ->toHaveCount(7)
        ->and(ViolationType::Spam->value)
        ->toBe('spam')
        ->and(ViolationType::Toxicity->value)
        ->toBe('toxicity')
        ->and(ViolationType::Harassment->value)
        ->toBe('harassment')
        ->and(ViolationType::Nsfw->value)
        ->toBe('nsfw')
        ->and(ViolationType::Raid->value)
        ->toBe('raid')
        ->and(ViolationType::Impersonation->value)
        ->toBe('impersonation')
        ->and(ViolationType::Other->value)
        ->toBe('other');
});

test('CaseStatus enum has all expected cases', function (): void {
    expect(CaseStatus::cases())
        ->toHaveCount(5)
        ->and(CaseStatus::Pending->value)
        ->toBe('pending')
        ->and(CaseStatus::Assigned->value)
        ->toBe('assigned')
        ->and(CaseStatus::Resolved->value)
        ->toBe('resolved')
        ->and(CaseStatus::Escalated->value)
        ->toBe('escalated')
        ->and(CaseStatus::Dismissed->value)
        ->toBe('dismissed');
});

test('CaseSource enum has all expected cases', function (): void {
    expect(CaseSource::cases())
        ->toHaveCount(4)
        ->and(CaseSource::UserReport->value)
        ->toBe('user_report')
        ->and(CaseSource::AutoDetect->value)
        ->toBe('auto_detect')
        ->and(CaseSource::RuleMatch->value)
        ->toBe('rule_match')
        ->and(CaseSource::ManualFlag->value)
        ->toBe('manual_flag');
});

test('AppealStatus enum has all expected cases', function (): void {
    expect(AppealStatus::cases())
        ->toHaveCount(4)
        ->and(AppealStatus::Pending->value)
        ->toBe('pending')
        ->and(AppealStatus::Reviewing->value)
        ->toBe('reviewing')
        ->and(AppealStatus::Upheld->value)
        ->toBe('upheld')
        ->and(AppealStatus::Overturned->value)
        ->toBe('overturned');
});

test('Severity enum has all expected cases', function (): void {
    expect(Severity::cases())
        ->toHaveCount(4)
        ->and(Severity::Low->value)
        ->toBe('low')
        ->and(Severity::Medium->value)
        ->toBe('medium')
        ->and(Severity::High->value)
        ->toBe('high')
        ->and(Severity::Critical->value)
        ->toBe('critical');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=EnumTest`
Expected: FAIL — classes not found

- [ ] **Step 3: Create all enum files**

`app-modules/moderation/src/Enums/Platform.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum Platform: string
{
    case Discord = 'discord';
    case Twitch = 'twitch';
    case GitHub = 'github';
    case Twitter = 'twitter';
    case Web = 'web';
}
```

`app-modules/moderation/src/Enums/ActionType.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum ActionType: string
{
    case Warn = 'warn';
    case Mute = 'mute';
    case Kick = 'kick';
    case Ban = 'ban';
    case Suspend = 'suspend';
    case ContentRemove = 'content_remove';
}
```

`app-modules/moderation/src/Enums/ViolationType.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

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
```

`app-modules/moderation/src/Enums/CaseStatus.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum CaseStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Dismissed = 'dismissed';
}
```

`app-modules/moderation/src/Enums/CaseSource.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum CaseSource: string
{
    case UserReport = 'user_report';
    case AutoDetect = 'auto_detect';
    case RuleMatch = 'rule_match';
    case ManualFlag = 'manual_flag';
}
```

`app-modules/moderation/src/Enums/AppealStatus.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum AppealStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Upheld = 'upheld';
    case Overturned = 'overturned';
}
```

`app-modules/moderation/src/Enums/Severity.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum Severity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=EnumTest`
Expected: PASS (7 tests)

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/Enums app-modules/moderation/tests/Unit/EnumTest.php
git commit -m "feat(moderation): add all domain enums with tests"
```

---

## Task 3: Contracts

**Files:**

- Create: `app-modules/moderation/src/Contracts/ModerationPlatformContract.php`
- Create: `app-modules/moderation/src/Contracts/ContentClassifierContract.php`
- Create: `app-modules/moderation/src/Contracts/PenaltyAdvisorContract.php`

- [ ] **Step 1: Create ModerationPlatformContract**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationAction;

interface ModerationPlatformContract
{
    public function platform(): Platform;

    public function ingest(array $rawPayload): ModerationContentDTO;

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO;

    public function notify(User $user, string $message, array $context = []): void;

    /** @return array<ActionType> */
    public function supports(): array;

    public function resolveUser(string $externalId): ?User;
}
```

- [ ] **Step 2: Create ContentClassifierContract**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;

interface ContentClassifierContract
{
    public function classify(ModerationContentDTO $content): ClassificationResultDTO;

    public function name(): string;
}
```

- [ ] **Step 3: Create PenaltyAdvisorContract**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Contracts;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

interface PenaltyAdvisorContract
{
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO;
}
```

- [ ] **Step 4: Commit**

```bash
git add app-modules/moderation/src/Contracts
git commit -m "feat(moderation): add platform, classifier, and advisor contracts"
```

---

## Task 4: DTOs

**Files:**

- Create: `app-modules/moderation/src/DTOs/ModerationContentDTO.php`
- Create: `app-modules/moderation/src/DTOs/ClassificationResultDTO.php`
- Create: `app-modules/moderation/src/DTOs/SuggestedPenaltyDTO.php`
- Create: `app-modules/moderation/src/DTOs/ExecutionResultDTO.php`
- Test: `app-modules/moderation/tests/Unit/DTOTest.php`

- [ ] **Step 1: Write DTO test**

```php
<?php

declare(strict_types=1);

use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

test('ModerationContentDTO holds all fields', function (): void {
    $dto = new ModerationContentDTO(
        contentId: 'msg-123',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '999888777',
        author: null,
        textContent: 'spam content here',
        mediaUrls: ['https://example.com/img.png'],
        metadata: ['channel_id' => '123', 'guild_id' => '456'],
        snapshot: ['raw' => 'full message data'],
        tenantId: 'tenant-uuid',
    );

    expect($dto->contentId)
        ->toBe('msg-123')
        ->and($dto->contentType)
        ->toBe('message')
        ->and($dto->sourcePlatform)
        ->toBe(Platform::Discord)
        ->and($dto->authorExternalId)
        ->toBe('999888777')
        ->and($dto->author)
        ->toBeNull()
        ->and($dto->textContent)
        ->toBe('spam content here')
        ->and($dto->mediaUrls)
        ->toBe(['https://example.com/img.png'])
        ->and($dto->metadata)
        ->toBe(['channel_id' => '123', 'guild_id' => '456'])
        ->and($dto->snapshot)
        ->toBe(['raw' => 'full message data'])
        ->and($dto->tenantId)
        ->toBe('tenant-uuid');
});

test('ClassificationResultDTO holds scores and primary violation', function (): void {
    $dto = new ClassificationResultDTO(
        scores: ['spam' => 0.92, 'toxicity' => 0.15],
        primary: ViolationType::Spam,
        severity: Severity::High,
        classifierName: 'openai',
        matchedRules: ['rule-uuid-1'],
    );

    expect($dto->scores)
        ->toBe(['spam' => 0.92, 'toxicity' => 0.15])
        ->and($dto->primary)
        ->toBe(ViolationType::Spam)
        ->and($dto->severity)
        ->toBe(Severity::High)
        ->and($dto->classifierName)
        ->toBe('openai')
        ->and($dto->matchedRules)
        ->toBe(['rule-uuid-1']);
});

test('SuggestedPenaltyDTO holds suggestion with reasoning', function (): void {
    $dto = new SuggestedPenaltyDTO(
        action: ActionType::Ban,
        duration: '7d',
        reasoning: '3rd offense in 30 days',
        priorOffenses: 3,
        history: [['type' => 'warn', 'date' => '2026-04-20']],
    );

    expect($dto->action)
        ->toBe(ActionType::Ban)
        ->and($dto->duration)
        ->toBe('7d')
        ->and($dto->reasoning)
        ->toBe('3rd offense in 30 days')
        ->and($dto->priorOffenses)
        ->toBe(3)
        ->and($dto->history)
        ->toHaveCount(1);
});

test('ExecutionResultDTO holds platform result', function (): void {
    $dto = new ExecutionResultDTO(
        platform: Platform::Discord,
        success: true,
        error: null,
        platformResponse: ['ban_id' => '123'],
    );

    expect($dto->platform)
        ->toBe(Platform::Discord)
        ->and($dto->success)
        ->toBeTrue()
        ->and($dto->error)
        ->toBeNull()
        ->and($dto->platformResponse)
        ->toBe(['ban_id' => '123']);
});

test('ExecutionResultDTO captures failure', function (): void {
    $dto = new ExecutionResultDTO(
        platform: Platform::Twitch,
        success: false,
        error: 'User not found on platform',
        platformResponse: [],
    );

    expect($dto->success)->toBeFalse()->and($dto->error)->toBe('User not found on platform');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DTOTest`
Expected: FAIL — classes not found

- [ ] **Step 3: Create all DTO files**

`app-modules/moderation/src/DTOs/ModerationContentDTO.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\Platform;

final readonly class ModerationContentDTO
{
    /**
     * @param  array<string>  $mediaUrls
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public string $contentId,
        public string $contentType,
        public Platform $sourcePlatform,
        public string $authorExternalId,
        public ?User $author,
        public string $textContent,
        public array $mediaUrls,
        public array $metadata,
        public array $snapshot,
        public ?string $tenantId,
    ) {}
}
```

`app-modules/moderation/src/DTOs/ClassificationResultDTO.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

final readonly class ClassificationResultDTO
{
    /**
     * @param  array<string, float>  $scores
     * @param  array<string>  $matchedRules
     */
    public function __construct(
        public array $scores,
        public ?ViolationType $primary,
        public ?Severity $severity,
        public string $classifierName,
        public array $matchedRules,
    ) {}
}
```

`app-modules/moderation/src/DTOs/SuggestedPenaltyDTO.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\ActionType;

final readonly class SuggestedPenaltyDTO
{
    /**
     * @param  array<array<string, mixed>>  $history
     */
    public function __construct(
        public ActionType $action,
        public ?string $duration,
        public string $reasoning,
        public int $priorOffenses,
        public array $history,
    ) {}
}
```

`app-modules/moderation/src/DTOs/ExecutionResultDTO.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\DTOs;

use He4rt\Moderation\Enums\Platform;

final readonly class ExecutionResultDTO
{
    /**
     * @param  array<string, mixed>  $platformResponse
     */
    public function __construct(
        public Platform $platform,
        public bool $success,
        public ?string $error,
        public array $platformResponse,
    ) {}
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=DTOTest`
Expected: PASS (5 tests)

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/DTOs app-modules/moderation/tests/Unit/DTOTest.php
git commit -m "feat(moderation): add DTOs for content, classification, penalty, and execution"
```

---

## Task 5: Database Migrations

**Files:**

- Create: `app-modules/moderation/database/migrations/2026_05_02_000001_create_moderation_cases_table.php`
- Create: `app-modules/moderation/database/migrations/2026_05_02_000002_create_moderation_reports_table.php`
- Create: `app-modules/moderation/database/migrations/2026_05_02_000003_create_moderation_actions_table.php`
- Create: `app-modules/moderation/database/migrations/2026_05_02_000004_create_moderation_appeals_table.php`
- Create: `app-modules/moderation/database/migrations/2026_05_02_000005_create_moderation_rules_table.php`
- Create: `app-modules/moderation/database/migrations/2026_05_02_000006_create_moderation_audit_log_table.php`

- [ ] **Step 1: Create moderation_cases migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_cases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('content_type', 50);
            $table->string('content_id', 255);
            $table->jsonb('content_snapshot')->nullable();
            $table->string('source_platform', 20);
            $table->string('source', 20);
            $table->string('status', 20)->default('pending');
            $table->integer('priority')->default(50);
            $table->string('severity', 20)->nullable();
            $table->string('violation_type', 30)->nullable();
            $table->jsonb('ai_scores')->nullable();
            $table->string('classifier_version', 50)->nullable();
            $table->string('suggested_action', 30)->nullable();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('assigned_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status', 'priority', 'created_at'], 'idx_cases_queue');
            $table->index(['author_id', 'created_at'], 'idx_cases_author');
            $table->index(['source_platform', 'created_at'], 'idx_cases_platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_cases');
    }
};
```

- [ ] **Step 2: Create moderation_reports migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('moderation_cases')->cascadeOnDelete();
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('platform', 20);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['case_id', 'reporter_id'], 'idx_reports_case_reporter');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');
    }
};
```

- [ ] **Step 3: Create moderation_actions migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_actions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('moderation_cases')->cascadeOnDelete();
            $table->foreignUuid('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30);
            $table->jsonb('target_platforms');
            $table->string('duration', 20)->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->jsonb('execution_results')->nullable();
            $table->boolean('automated')->default(false);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['case_id'], 'idx_actions_case');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_actions');
    }
};
```

- [ ] **Step 4: Create moderation_appeals migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_appeals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('action_id')->constrained('moderation_actions')->cascadeOnDelete();
            $table->foreignUuid('appellant_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason_category', 50);
            $table->text('reason_text')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('sla_deadline');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['status', 'sla_deadline'], 'idx_appeals_sla');
            $table->index(['action_id'], 'idx_appeals_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_appeals');
    }
};
```

- [ ] **Step 5: Create moderation_rules migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->string('platform', 20)->nullable();
            $table->text('pattern');
            $table->string('violation_type', 30);
            $table->string('severity', 20);
            $table->string('action_on_match', 30);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['is_active', 'tenant_id'], 'idx_rules_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_rules');
    }
};
```

- [ ] **Step 6: Create moderation_audit_log migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moderation_audit_log', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 50);
            $table->uuid('actor_id')->nullable();
            $table->string('actor_type', 20)->nullable();
            $table->uuid('case_id')->nullable();
            $table->jsonb('details');
            $table->string('platform', 20)->nullable();
            $table->uuid('tenant_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at'], 'idx_audit_tenant_date');
            $table->index(['case_id'], 'idx_audit_case');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_audit_log');
    }
};
```

- [ ] **Step 7: Run migrations**

Run: `php artisan migrate`
Expected: All 6 tables created successfully

- [ ] **Step 8: Commit**

```bash
git add app-modules/moderation/database/migrations
git commit -m "feat(moderation): add database migrations for all moderation tables"
```

---

## Task 6: Models & Factories

**Files:**

- Create: `app-modules/moderation/src/Models/ModerationCase.php`
- Create: `app-modules/moderation/src/Models/ModerationReport.php`
- Create: `app-modules/moderation/src/Models/ModerationAction.php`
- Create: `app-modules/moderation/src/Models/ModerationAppeal.php`
- Create: `app-modules/moderation/src/Models/ModerationRule.php`
- Create: `app-modules/moderation/src/Models/ModerationAuditLog.php`
- Create: `app-modules/moderation/database/factories/ModerationCaseFactory.php`
- Create: `app-modules/moderation/database/factories/ModerationReportFactory.php`
- Create: `app-modules/moderation/database/factories/ModerationActionFactory.php`
- Create: `app-modules/moderation/database/factories/ModerationAppealFactory.php`
- Create: `app-modules/moderation/database/factories/ModerationRuleFactory.php`
- Test: `app-modules/moderation/tests/Feature/ModelRelationshipTest.php`

- [ ] **Step 1: Write model relationship test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ModerationCase has reports', function (): void {
    $case = ModerationCase::factory()->create();
    $report = ModerationReport::factory()->create(['case_id' => $case->id]);

    expect($case->reports)
        ->toHaveCount(1)
        ->and($case->reports->first()->id)
        ->toBe($report->id);
});

test('ModerationCase has actions', function (): void {
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create(['case_id' => $case->id]);

    expect($case->actions)
        ->toHaveCount(1)
        ->and($case->actions->first()->id)
        ->toBe($action->id);
});

test('ModerationCase belongs to author', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);

    expect($case->author->id)->toBe($user->id);
});

test('ModerationCase belongs to assignee', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create(['assigned_to' => $moderator->id]);

    expect($case->assignee->id)->toBe($moderator->id);
});

test('ModerationCase belongs to tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $case = ModerationCase::factory()->create(['tenant_id' => $tenant->id]);

    expect($case->tenant->id)->toBe($tenant->id);
});

test('ModerationAction belongs to case', function (): void {
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create(['case_id' => $case->id]);

    expect($action->case->id)->toBe($case->id);
});

test('ModerationAction has one appeal', function (): void {
    $action = ModerationAction::factory()->create();
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    expect($action->appeal->id)->toBe($appeal->id);
});

test('ModerationAppeal belongs to action', function (): void {
    $action = ModerationAction::factory()->create();
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    expect($appeal->action->id)->toBe($action->id);
});

test('ModerationReport belongs to case', function (): void {
    $case = ModerationCase::factory()->create();
    $report = ModerationReport::factory()->create(['case_id' => $case->id]);

    expect($report->case->id)->toBe($case->id);
});

test('ModerationCase casts enums correctly', function (): void {
    $case = ModerationCase::factory()->create([
        'source_platform' => 'discord',
        'source' => 'user_report',
        'status' => 'pending',
        'severity' => 'high',
        'violation_type' => 'spam',
    ]);

    $case->refresh();

    expect($case->source_platform)
        ->toBe(\He4rt\Moderation\Enums\Platform::Discord)
        ->and($case->source)
        ->toBe(\He4rt\Moderation\Enums\CaseSource::UserReport)
        ->and($case->status)
        ->toBe(\He4rt\Moderation\Enums\CaseStatus::Pending)
        ->and($case->severity)
        ->toBe(\He4rt\Moderation\Enums\Severity::High)
        ->and($case->violation_type)
        ->toBe(\He4rt\Moderation\Enums\ViolationType::Spam);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ModelRelationshipTest`
Expected: FAIL — models not found

- [ ] **Step 3: Create ModerationCase model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationCaseFactory;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ModerationCase extends Model
{
    /** @use HasFactory<ModerationCaseFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'moderation_cases';

    protected $fillable = [
        'content_type',
        'content_id',
        'content_snapshot',
        'source_platform',
        'source',
        'status',
        'priority',
        'severity',
        'violation_type',
        'ai_scores',
        'classifier_version',
        'suggested_action',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'author_id',
        'tenant_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'source_platform' => Platform::class,
            'source' => CaseSource::class,
            'status' => CaseStatus::class,
            'severity' => Severity::class,
            'violation_type' => ViolationType::class,
            'suggested_action' => ActionType::class,
            'ai_scores' => 'array',
            'content_snapshot' => 'array',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<ModerationReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(ModerationReport::class, 'case_id');
    }

    /** @return HasMany<ModerationAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'case_id');
    }

    protected static function newFactory(): ModerationCaseFactory
    {
        return ModerationCaseFactory::new();
    }
}
```

- [ ] **Step 4: Create ModerationReport model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationReportFactory;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationReport extends Model
{
    /** @use HasFactory<ModerationReportFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'moderation_reports';

    public $timestamps = false;

    protected $fillable = ['case_id', 'reporter_id', 'reason', 'details', 'platform'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'reason' => ViolationType::class,
            'platform' => Platform::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ModerationCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    protected static function newFactory(): ModerationReportFactory
    {
        return ModerationReportFactory::new();
    }
}
```

- [ ] **Step 5: Create ModerationAction model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationActionFactory;
use He4rt\Moderation\Enums\ActionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class ModerationAction extends Model
{
    /** @use HasFactory<ModerationActionFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'moderation_actions';

    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'moderator_id',
        'action_type',
        'target_platforms',
        'duration',
        'reason',
        'metadata',
        'execution_results',
        'automated',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'action_type' => ActionType::class,
            'target_platforms' => 'array',
            'metadata' => 'array',
            'execution_results' => 'array',
            'automated' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ModerationCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    /** @return HasOne<ModerationAppeal, $this> */
    public function appeal(): HasOne
    {
        return $this->hasOne(ModerationAppeal::class, 'action_id');
    }

    protected static function newFactory(): ModerationActionFactory
    {
        return ModerationActionFactory::new();
    }
}
```

- [ ] **Step 6: Create ModerationAppeal model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Database\Factories\ModerationAppealFactory;
use He4rt\Moderation\Enums\AppealStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationAppeal extends Model
{
    /** @use HasFactory<ModerationAppealFactory> */
    use HasFactory;
    use HasUuids;

    protected $table = 'moderation_appeals';

    public $timestamps = false;

    protected $fillable = [
        'action_id',
        'appellant_id',
        'reason_category',
        'reason_text',
        'status',
        'reviewer_id',
        'reviewer_notes',
        'resolved_at',
        'sla_deadline',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'resolved_at' => 'datetime',
            'sla_deadline' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ModerationAction, $this> */
    public function action(): BelongsTo
    {
        return $this->belongsTo(ModerationAction::class, 'action_id');
    }

    /** @return BelongsTo<User, $this> */
    public function appellant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appellant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    protected static function newFactory(): ModerationAppealFactory
    {
        return ModerationAppealFactory::new();
    }
}
```

- [ ] **Step 7: Create ModerationRule model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ModerationRule extends Model
{
    use HasUuids;

    protected $table = 'moderation_rules';

    protected $fillable = [
        'name',
        'type',
        'platform',
        'pattern',
        'violation_type',
        'severity',
        'action_on_match',
        'is_active',
        'tenant_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'violation_type' => ViolationType::class,
            'severity' => Severity::class,
            'action_on_match' => ActionType::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

- [ ] **Step 8: Create ModerationAuditLog model**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Models;

use Illuminate\Database\Eloquent\Model;

final class ModerationAuditLog extends Model
{
    protected $table = 'moderation_audit_log';

    public $timestamps = false;

    protected $fillable = ['event_type', 'actor_id', 'actor_type', 'case_id', 'details', 'platform', 'tenant_id'];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 9: Create ModerationCaseFactory**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationCase> */
final class ModerationCaseFactory extends Factory
{
    protected $model = ModerationCase::class;

    public function definition(): array
    {
        return [
            'content_type' => 'message',
            'content_id' => fake()->uuid(),
            'content_snapshot' => ['text' => fake()->sentence()],
            'source_platform' => 'discord',
            'source' => 'user_report',
            'status' => 'pending',
            'priority' => fake()->numberBetween(0, 100),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'violation_type' => fake()->randomElement(['spam', 'toxicity', 'harassment']),
            'ai_scores' => ['spam' => fake()->randomFloat(2, 0, 1)],
            'author_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 95, 'severity' => 'critical']);
    }
}
```

- [ ] **Step 10: Create ModerationReportFactory**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationReport> */
final class ModerationReportFactory extends Factory
{
    protected $model = ModerationReport::class;

    public function definition(): array
    {
        return [
            'case_id' => ModerationCase::factory(),
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(['spam', 'toxicity', 'harassment']),
            'details' => fake()->sentence(),
            'platform' => 'discord',
        ];
    }
}
```

- [ ] **Step 11: Create ModerationActionFactory**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationAction> */
final class ModerationActionFactory extends Factory
{
    protected $model = ModerationAction::class;

    public function definition(): array
    {
        return [
            'case_id' => ModerationCase::factory(),
            'moderator_id' => User::factory(),
            'action_type' => fake()->randomElement(['warn', 'mute', 'ban']),
            'target_platforms' => ['discord'],
            'duration' => '7d',
            'reason' => fake()->sentence(),
            'automated' => false,
        ];
    }
}
```

- [ ] **Step 12: Create ModerationAppealFactory**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModerationAppeal> */
final class ModerationAppealFactory extends Factory
{
    protected $model = ModerationAppeal::class;

    public function definition(): array
    {
        return [
            'action_id' => ModerationAction::factory(),
            'appellant_id' => User::factory(),
            'reason_category' => 'context_misunderstood',
            'reason_text' => fake()->sentence(),
            'status' => 'pending',
            'sla_deadline' => now()->addHours(48),
        ];
    }
}
```

- [ ] **Step 13: Run tests to verify they pass**

Run: `php artisan test --filter=ModelRelationshipTest`
Expected: PASS (10 tests)

- [ ] **Step 14: Commit**

```bash
git add app-modules/moderation/src/Models app-modules/moderation/database/factories app-modules/moderation/tests/Feature/ModelRelationshipTest.php
git commit -m "feat(moderation): add models, factories, and relationship tests"
```

---

## Task 7: Classifiers

**Files:**

- Create: `app-modules/moderation/src/Classifiers/RuleBasedClassifier.php`
- Create: `app-modules/moderation/src/Classifiers/OpenAiClassifier.php`
- Create: `app-modules/moderation/src/Classifiers/AggregateClassifier.php`
- Test: `app-modules/moderation/tests/Unit/ClassifierTest.php`

- [ ] **Step 1: Write classifier tests**

```php
<?php

declare(strict_types=1);

use He4rt\Moderation\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeContentDTO(string $text = 'hello world'): ModerationContentDTO
{
    return new ModerationContentDTO(
        contentId: 'msg-1',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '123',
        author: null,
        textContent: $text,
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => $text],
        tenantId: null,
    );
}

test('RuleBasedClassifier matches keyword rule', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam keywords',
        'type' => 'keyword',
        'pattern' => 'buy followers,cheap followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'warn',
        'is_active' => true,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('Buy followers now! Best price'));

    expect($result->scores['spam'])
        ->toBeGreaterThanOrEqual(0.9)
        ->and($result->primary)
        ->toBe(ViolationType::Spam)
        ->and($result->matchedRules)
        ->toHaveCount(1);
});

test('RuleBasedClassifier matches regex rule', function (): void {
    ModerationRule::query()->create([
        'name' => 'Crypto scam URLs',
        'type' => 'regex',
        'pattern' => 'https?://(crypto|nft|airdrop).*\.(xyz|click)',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('Check out https://crypto-free.xyz'));

    expect($result->scores['spam'])->toBeGreaterThanOrEqual(0.9)->and($result->matchedRules)->toHaveCount(1);
});

test('RuleBasedClassifier ignores inactive rules', function (): void {
    ModerationRule::query()->create([
        'name' => 'Disabled rule',
        'type' => 'keyword',
        'pattern' => 'hello',
        'violation_type' => 'spam',
        'severity' => 'low',
        'action_on_match' => 'warn',
        'is_active' => false,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('hello world'));

    expect($result->scores)->toBeEmpty()->and($result->primary)->toBeNull();
});

test('RuleBasedClassifier returns empty for no matches', function (): void {
    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('just a normal message'));

    expect($result->scores)->toBeEmpty()->and($result->primary)->toBeNull()->and($result->matchedRules)->toBeEmpty();
});

test('OpenAiClassifier calls API and returns scores', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => [
                        'harassment' => true,
                        'hate' => false,
                        'sexual' => false,
                        'violence' => false,
                        'self-harm' => false,
                    ],
                    'category_scores' => [
                        'harassment' => 0.85,
                        'hate' => 0.12,
                        'sexual' => 0.01,
                        'violence' => 0.03,
                        'self-harm' => 0.0,
                    ],
                ],
            ],
        ]),
    ]);

    $classifier = new OpenAiClassifier();
    $result = $classifier->classify(makeContentDTO('you are terrible'));

    expect($result->scores)
        ->toHaveKey('harassment')
        ->and($result->scores['harassment'])
        ->toBe(0.85)
        ->and($result->primary)
        ->toBe(ViolationType::Harassment);
});

test('OpenAiClassifier handles API failure gracefully', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([], 500),
    ]);

    $classifier = new OpenAiClassifier();
    $result = $classifier->classify(makeContentDTO('test content'));

    expect($result->scores)
        ->toBeEmpty()
        ->and($result->primary)
        ->toBeNull()
        ->and($result->classifierName)
        ->toBe('openai');
});

test('AggregateClassifier merges results from multiple classifiers', function (): void {
    $ruleResult = new ClassificationResultDTO(
        scores: ['spam' => 0.95],
        primary: ViolationType::Spam,
        severity: Severity::High,
        classifierName: 'rules',
        matchedRules: ['rule-1'],
    );

    $aiResult = new ClassificationResultDTO(
        scores: ['spam' => 0.6, 'toxicity' => 0.3],
        primary: ViolationType::Spam,
        severity: Severity::Medium,
        classifierName: 'openai',
        matchedRules: [],
    );

    $mockRule = Mockery::mock(ContentClassifierContract::class);
    $mockRule->shouldReceive('classify')->andReturn($ruleResult);

    $mockAi = Mockery::mock(ContentClassifierContract::class);
    $mockAi->shouldReceive('classify')->andReturn($aiResult);

    $aggregate = new AggregateClassifier([$mockRule, $mockAi]);
    $result = $aggregate->classify(makeContentDTO('spam content'));

    expect($result->scores['spam'])
        ->toBe(0.95)
        ->and($result->scores['toxicity'])
        ->toBe(0.3)
        ->and($result->primary)
        ->toBe(ViolationType::Spam)
        ->and($result->severity)
        ->toBe(Severity::High)
        ->and($result->matchedRules)
        ->toBe(['rule-1']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ClassifierTest`
Expected: FAIL — classes not found

- [ ] **Step 3: Create RuleBasedClassifier**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classifiers;

use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationRule;

final class RuleBasedClassifier implements ContentClassifierContract
{
    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        $rules = ModerationRule::query()
            ->where('is_active', true)
            ->when(
                $content->tenantId,
                fn($q) => $q->where(function ($q) use ($content) {
                    $q->where('tenant_id', $content->tenantId)->orWhereNull('tenant_id');
                }),
            )
            ->get();

        $scores = [];
        $matchedRules = [];
        $highestSeverity = null;

        foreach ($rules as $rule) {
            if ($this->matches($rule, $content->textContent)) {
                $violationType = $rule->violation_type->value;
                $scores[$violationType] = max($scores[$violationType] ?? 0, 0.95);
                $matchedRules[] = $rule->id;

                if (
                    $highestSeverity === null ||
                    $this->severityWeight($rule->severity) > $this->severityWeight($highestSeverity)
                ) {
                    $highestSeverity = $rule->severity;
                }
            }
        }

        $primary = !empty($scores) ? ViolationType::from(array_key_first($scores)) : null;

        return new ClassificationResultDTO(
            scores: $scores,
            primary: $primary,
            severity: $highestSeverity,
            classifierName: 'rules',
            matchedRules: $matchedRules,
        );
    }

    public function name(): string
    {
        return 'rules';
    }

    private function matches(ModerationRule $rule, string $text): bool
    {
        return match ($rule->type) {
            'keyword' => $this->matchesKeyword($rule->pattern, $text),
            'regex' => $this->matchesRegex($rule->pattern, $text),
            default => false,
        };
    }

    private function matchesKeyword(string $pattern, string $text): bool
    {
        $keywords = array_map('trim', explode(',', $pattern));
        $lowerText = mb_strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function matchesRegex(string $pattern, string $text): bool
    {
        return (bool) preg_match('/' . $pattern . '/i', $text);
    }

    private function severityWeight(Severity $severity): int
    {
        return match ($severity) {
            Severity::Low => 1,
            Severity::Medium => 2,
            Severity::High => 3,
            Severity::Critical => 4,
        };
    }
}
```

- [ ] **Step 4: Create OpenAiClassifier**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classifiers;

use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAiClassifier implements ContentClassifierContract
{
    private const CATEGORY_MAP = [
        'harassment' => 'harassment',
        'hate' => 'toxicity',
        'sexual' => 'nsfw',
        'violence' => 'toxicity',
        'self-harm' => 'toxicity',
        'sexual/minors' => 'nsfw',
        'hate/threatening' => 'harassment',
        'violence/graphic' => 'toxicity',
        'harassment/threatening' => 'harassment',
    ];

    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        if (!config('moderation.classifiers.openai.enabled')) {
            return $this->emptyResult();
        }

        try {
            $response = Http::withToken(config('services.openai.api_key'))->post(
                'https://api.openai.com/v1/moderations',
                [
                    'model' => config('moderation.classifiers.openai.model', 'omni-moderation-latest'),
                    'input' => $content->textContent,
                ],
            );

            if ($response->failed()) {
                Log::warning('OpenAI Moderation API failed', ['status' => $response->status()]);

                return $this->emptyResult();
            }

            return $this->parseResponse($response->json());
        } catch (\Throwable $e) {
            Log::warning('OpenAI Moderation API exception', ['error' => $e->getMessage()]);

            return $this->emptyResult();
        }
    }

    public function name(): string
    {
        return 'openai';
    }

    private function parseResponse(array $data): ClassificationResultDTO
    {
        $result = $data['results'][0] ?? [];
        $categoryScores = $result['category_scores'] ?? [];

        $scores = [];
        foreach ($categoryScores as $category => $score) {
            $mapped = self::CATEGORY_MAP[$category] ?? null;
            if ($mapped !== null) {
                $scores[$mapped] = max($scores[$mapped] ?? 0, $score);
            }
        }

        $primary = null;
        $highestScore = 0;
        foreach ($scores as $type => $score) {
            if ($score > $highestScore) {
                $highestScore = $score;
                $primary = ViolationType::tryFrom($type);
            }
        }

        $severity = match (true) {
            $highestScore >= 0.9 => Severity::Critical,
            $highestScore >= 0.7 => Severity::High,
            $highestScore >= 0.4 => Severity::Medium,
            default => Severity::Low,
        };

        return new ClassificationResultDTO(
            scores: $scores,
            primary: $primary,
            severity: $highestScore > 0 ? $severity : null,
            classifierName: 'openai',
            matchedRules: [],
        );
    }

    private function emptyResult(): ClassificationResultDTO
    {
        return new ClassificationResultDTO(
            scores: [],
            primary: null,
            severity: null,
            classifierName: 'openai',
            matchedRules: [],
        );
    }
}
```

- [ ] **Step 5: Create AggregateClassifier**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classifiers;

use He4rt\Moderation\Contracts\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

final class AggregateClassifier implements ContentClassifierContract
{
    /** @param  array<ContentClassifierContract>  $classifiers */
    public function __construct(private readonly array $classifiers) {}

    public function classify(ModerationContentDTO $content): ClassificationResultDTO
    {
        $mergedScores = [];
        $allMatchedRules = [];
        $highestSeverity = null;

        foreach ($this->classifiers as $classifier) {
            $result = $classifier->classify($content);

            foreach ($result->scores as $type => $score) {
                $mergedScores[$type] = max($mergedScores[$type] ?? 0, $score);
            }

            $allMatchedRules = array_merge($allMatchedRules, $result->matchedRules);

            if ($result->severity !== null) {
                if (
                    $highestSeverity === null ||
                    $this->severityWeight($result->severity) > $this->severityWeight($highestSeverity)
                ) {
                    $highestSeverity = $result->severity;
                }
            }
        }

        $primary = null;
        $highestScore = 0;
        foreach ($mergedScores as $type => $score) {
            if ($score > $highestScore) {
                $highestScore = $score;
                $primary = ViolationType::tryFrom($type);
            }
        }

        return new ClassificationResultDTO(
            scores: $mergedScores,
            primary: $primary,
            severity: $highestSeverity,
            classifierName: 'aggregate',
            matchedRules: $allMatchedRules,
        );
    }

    public function name(): string
    {
        return 'aggregate';
    }

    private function severityWeight(Severity $severity): int
    {
        return match ($severity) {
            Severity::Low => 1,
            Severity::Medium => 2,
            Severity::High => 3,
            Severity::Critical => 4,
        };
    }
}
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ClassifierTest`
Expected: PASS (7 tests)

- [ ] **Step 7: Commit**

```bash
git add app-modules/moderation/src/Classifiers app-modules/moderation/tests/Unit/ClassifierTest.php
git commit -m "feat(moderation): add rule-based, OpenAI, and aggregate classifiers"
```

---

## Task 8: Penalty Advisor

**Files:**

- Create: `app-modules/moderation/src/Advisors/HistoryBasedPenaltyAdvisor.php`
- Test: `app-modules/moderation/tests/Unit/PenaltyAdvisorTest.php`

- [ ] **Step 1: Write penalty advisor test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('suggests warn for first offense low severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->action)
        ->toBe(ActionType::Warn)
        ->and($result->duration)
        ->toBeNull()
        ->and($result->priorOffenses)
        ->toBe(0);
});

test('suggests warn for first offense medium severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)->toBe(ActionType::Warn)->and($result->duration)->toBeNull();
});

test('suggests mute 24h for first offense high severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Mute)->and($result->duration)->toBe('24h');
});

test('suggests mute 24h for second offense medium severity', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(5)]);

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)
        ->toBe(ActionType::Mute)
        ->and($result->duration)
        ->toBe('24h')
        ->and($result->priorOffenses)
        ->toBe(1);
});

test('suggests ban 7d for third offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 2; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(10 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Medium);

    expect($result->action)
        ->toBe(ActionType::Ban)
        ->and($result->duration)
        ->toBe('7d')
        ->and($result->priorOffenses)
        ->toBe(2);
});

test('suggests ban 30d for fourth offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(15 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)
        ->toBe(ActionType::Ban)
        ->and($result->duration)
        ->toBe('30d')
        ->and($result->priorOffenses)
        ->toBe(3);
});

test('suggests permanent ban for fifth offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(20 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->action)
        ->toBe(ActionType::Ban)
        ->and($result->duration)
        ->toBe('permanent')
        ->and($result->priorOffenses)
        ->toBe(5);
});

test('only counts offenses within escalation window', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(45)]);

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->priorOffenses)->toBe(0)->and($result->action)->toBe(ActionType::Warn);
});

test('includes history in suggestion', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'author_id' => $user->id,
        'status' => 'resolved',
        'violation_type' => 'spam',
    ]);
    ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'warn',
        'created_at' => now()->subDays(5),
    ]);

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->history)->toHaveCount(1)->and($result->reasoning)->toContain('1');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=PenaltyAdvisorTest`
Expected: FAIL

- [ ] **Step 3: Create HistoryBasedPenaltyAdvisor**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Advisors;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\PenaltyAdvisorContract;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;

final class HistoryBasedPenaltyAdvisor implements PenaltyAdvisorContract
{
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO
    {
        $windowDays = config('moderation.penalties.escalation_window_days', 30);

        $priorActions = ModerationAction::query()
            ->whereHas('case', fn($q) => $q->where('author_id', $user->id)->where('status', 'resolved'))
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->orderBy('created_at', 'desc')
            ->get();

        $priorOffenses = $priorActions->count();

        $history = $priorActions
            ->map(
                fn(ModerationAction $action) => [
                    'type' => $action->action_type->value,
                    'date' => $action->created_at->toDateString(),
                    'violation' => $action->case?->violation_type?->value,
                ],
            )
            ->toArray();

        [$action, $duration] = $this->escalate($priorOffenses, $severity);

        $reasoning = $this->buildReasoning($priorOffenses, $severity, $windowDays);

        return new SuggestedPenaltyDTO(
            action: $action,
            duration: $duration,
            reasoning: $reasoning,
            priorOffenses: $priorOffenses,
            history: $history,
        );
    }

    /** @return array{ActionType, ?string} */
    private function escalate(int $priorOffenses, Severity $severity): array
    {
        if ($priorOffenses >= 5) {
            return [ActionType::Ban, 'permanent'];
        }

        if ($priorOffenses >= 3) {
            return [ActionType::Ban, '30d'];
        }

        if ($priorOffenses >= 2) {
            return [ActionType::Ban, '7d'];
        }

        if ($priorOffenses === 1) {
            return match (true) {
                $severity === Severity::High || $severity === Severity::Critical => [ActionType::Mute, '7d'],
                $severity === Severity::Medium => [ActionType::Mute, '24h'],
                default => [ActionType::Warn, null],
            };
        }

        return match (true) {
            $severity === Severity::High || $severity === Severity::Critical => [ActionType::Mute, '24h'],
            default => [ActionType::Warn, null],
        };
    }

    private function buildReasoning(int $priorOffenses, Severity $severity, int $windowDays): string
    {
        if ($priorOffenses === 0) {
            return "First offense ({$severity->value} severity). No prior actions in last {$windowDays} days.";
        }

        return "{$priorOffenses} prior offense(s) in last {$windowDays} days. Severity: {$severity->value}.";
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=PenaltyAdvisorTest`
Expected: PASS (9 tests)

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/Advisors app-modules/moderation/tests/Unit/PenaltyAdvisorTest.php
git commit -m "feat(moderation): add history-based penalty advisor with escalation logic"
```

---

## Task 9: Pipeline Jobs

**Files:**

- Create: `app-modules/moderation/src/Jobs/IngestContent.php`
- Create: `app-modules/moderation/src/Jobs/ClassifyContent.php`
- Create: `app-modules/moderation/src/Jobs/RouteDecision.php`
- Create: `app-modules/moderation/src/Jobs/ExecuteAction.php`
- Test: `app-modules/moderation/tests/Feature/PipelineTest.php`

- [ ] **Step 1: Write pipeline integration test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Jobs\ClassifyContent;
use He4rt\Moderation\Jobs\IngestContent;
use He4rt\Moderation\Jobs\RouteDecision;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('IngestContent creates a ModerationCase from DTO', function (): void {
    $user = User::factory()->create();
    $dto = new ModerationContentDTO(
        contentId: 'msg-999',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '12345',
        author: $user,
        textContent: 'some spam content',
        mediaUrls: [],
        metadata: ['channel_id' => 'ch-1'],
        snapshot: ['text' => 'some spam content'],
        tenantId: null,
    );

    $job = new IngestContent($dto, CaseSource::UserReport);
    $case = $job->handle();

    expect($case)
        ->toBeInstanceOf(ModerationCase::class)
        ->and($case->content_id)
        ->toBe('msg-999')
        ->and($case->content_type)
        ->toBe('message')
        ->and($case->source_platform)
        ->toBe(Platform::Discord)
        ->and($case->source)
        ->toBe(CaseSource::UserReport)
        ->and($case->status)
        ->toBe(CaseStatus::Pending)
        ->and($case->author_id)
        ->toBe($user->id)
        ->and($case->content_snapshot)
        ->toBe(['text' => 'some spam content']);
});

test('ClassifyContent updates case with AI scores', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam test',
        'type' => 'keyword',
        'pattern' => 'buy followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'warn',
        'is_active' => true,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [
                [
                    'flagged' => true,
                    'categories' => ['harassment' => false],
                    'category_scores' => ['harassment' => 0.1],
                ],
            ],
        ]),
    ]);

    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'buy followers now'],
    ]);

    $job = new ClassifyContent($case);
    $job->handle();

    $case->refresh();
    expect($case->ai_scores)
        ->toHaveKey('spam')
        ->and($case->ai_scores['spam'])
        ->toBeGreaterThanOrEqual(0.9)
        ->and($case->violation_type->value)
        ->toBe('spam')
        ->and($case->severity->value)
        ->toBe('high');
});

test('RouteDecision flags case when score exceeds threshold', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.85],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
    ]);

    $job = new RouteDecision($case);
    $job->handle();

    $case->refresh();
    expect($case->status)
        ->toBe(CaseStatus::Pending)
        ->and($case->priority)
        ->toBeGreaterThan(50)
        ->and($case->suggested_action)
        ->not->toBeNull();
});

test('RouteDecision dismisses case when all scores below threshold', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.1, 'toxicity' => 0.05],
        'status' => 'pending',
    ]);

    $job = new RouteDecision($case);
    $job->handle();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('full pipeline flow: ingest -> classify -> route', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam URLs',
        'type' => 'keyword',
        'pattern' => 'free followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [
                [
                    'flagged' => false,
                    'categories' => [],
                    'category_scores' => ['harassment' => 0.02],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $dto = new ModerationContentDTO(
        contentId: 'msg-full-test',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-1',
        author: $user,
        textContent: 'Get free followers at spam.com',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'Get free followers at spam.com'],
        tenantId: null,
    );

    $case = new IngestContent($dto, CaseSource::AutoDetect)->handle();
    new ClassifyContent($case)->handle();
    new RouteDecision($case)->handle();

    $case->refresh();
    expect($case->status)
        ->toBe(CaseStatus::Pending)
        ->and($case->ai_scores['spam'])
        ->toBeGreaterThanOrEqual(0.9)
        ->and($case->suggested_action)
        ->not->toBeNull()
        ->and($case->priority)
        ->toBeGreaterThan(50);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=PipelineTest`
Expected: FAIL

- [ ] **Step 3: Create IngestContent job**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class IngestContent implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationContentDTO $content, private readonly CaseSource $source) {}

    public function handle(): ModerationCase
    {
        return ModerationCase::query()->create([
            'content_type' => $this->content->contentType,
            'content_id' => $this->content->contentId,
            'content_snapshot' => $this->content->snapshot,
            'source_platform' => $this->content->sourcePlatform,
            'source' => $this->source,
            'status' => CaseStatus::Pending,
            'priority' => 50,
            'author_id' => $this->content->author?->id,
            'tenant_id' => $this->content->tenantId,
        ]);
    }
}
```

- [ ] **Step 4: Create ClassifyContent job**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Moderation\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class ClassifyContent implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(): void
    {
        $content = new ModerationContentDTO(
            contentId: $this->case->content_id,
            contentType: $this->case->content_type,
            sourcePlatform: $this->case->source_platform ?? Platform::Web,
            authorExternalId: '',
            author: $this->case->author,
            textContent: $this->case->content_snapshot['text'] ?? '',
            mediaUrls: $this->case->content_snapshot['media_urls'] ?? [],
            metadata: $this->case->content_snapshot['metadata'] ?? [],
            snapshot: $this->case->content_snapshot ?? [],
            tenantId: $this->case->tenant_id,
        );

        $classifier = new AggregateClassifier([new RuleBasedClassifier(), new OpenAiClassifier()]);

        $result = $classifier->classify($content);

        $this->case->update([
            'ai_scores' => $result->scores,
            'violation_type' => $result->primary,
            'severity' => $result->severity,
            'classifier_version' => $result->classifierName,
        ]);
    }
}
```

- [ ] **Step 5: Create RouteDecision job**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Moderation\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class RouteDecision implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(): void
    {
        $scores = $this->case->ai_scores ?? [];
        $maxScore = !empty($scores) ? max($scores) : 0;

        $dismissThreshold = config('moderation.thresholds.dismiss', 0.3);
        $flagThreshold = config('moderation.thresholds.flag', 0.7);
        $highPriorityThreshold = config('moderation.thresholds.high_priority', 0.9);

        if ($maxScore < $dismissThreshold) {
            $this->case->update(['status' => CaseStatus::Dismissed]);

            return;
        }

        if ($maxScore < $flagThreshold) {
            $this->case->update(['status' => CaseStatus::Dismissed]);

            return;
        }

        $priority = (int) ($maxScore * 100);

        if ($maxScore >= $highPriorityThreshold) {
            $priority = min($priority + 10, 100);
        }

        $reportBoost = $this->case->reports()->count() * 10;
        $priority = min($priority + $reportBoost, 100);

        $suggestedAction = null;
        if ($this->case->author && $this->case->violation_type && $this->case->severity) {
            $advisor = new HistoryBasedPenaltyAdvisor();
            $suggestion = $advisor->suggest($this->case->author, $this->case->violation_type, $this->case->severity);
            $suggestedAction = $suggestion->action;
        }

        $this->case->update([
            'status' => CaseStatus::Pending,
            'priority' => $priority,
            'suggested_action' => $suggestedAction,
        ]);
    }
}
```

- [ ] **Step 6: Create ExecuteAction job**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Jobs;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\ModerationPlatformContract;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Events\ActionExecuted;
use He4rt\Moderation\Models\ModerationAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class ExecuteAction implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationAction $action, private readonly User $target) {}

    public function handle(): void
    {
        $platforms = app()->tagged('moderation.platforms');
        $results = [];

        foreach ($platforms as $adapter) {
            /** @var ModerationPlatformContract $adapter */
            if (in_array($adapter->platform()->value, $this->action->target_platforms, true)) {
                $results[] = $adapter->execute($this->action, $this->target);
            }
        }

        $this->action->update([
            'execution_results' => array_map(
                fn($r) => [
                    'platform' => $r->platform->value,
                    'success' => $r->success,
                    'error' => $r->error,
                ],
                $results,
            ),
        ]);

        $this->action->case->update([
            'status' => CaseStatus::Resolved,
            'resolved_at' => now(),
        ]);

        ActionExecuted::dispatch($this->action);
    }
}
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=PipelineTest`
Expected: PASS (5 tests)

- [ ] **Step 8: Commit**

```bash
git add app-modules/moderation/src/Jobs app-modules/moderation/tests/Feature/PipelineTest.php
git commit -m "feat(moderation): add pipeline jobs (ingest, classify, route, execute)"
```

---

## Task 10: Events & Listeners

**Files:**

- Create: `app-modules/moderation/src/Events/CaseCreated.php`
- Create: `app-modules/moderation/src/Events/CaseResolved.php`
- Create: `app-modules/moderation/src/Events/ActionExecuted.php`
- Create: `app-modules/moderation/src/Listeners/RecordAuditLog.php`

- [ ] **Step 1: Create event classes**

`app-modules/moderation/src/Events/CaseCreated.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Events;

use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

final class CaseCreated
{
    use Dispatchable;

    public function __construct(public readonly ModerationCase $case) {}
}
```

`app-modules/moderation/src/Events/CaseResolved.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Events;

use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Events\Dispatchable;

final class CaseResolved
{
    use Dispatchable;

    public function __construct(public readonly ModerationCase $case) {}
}
```

`app-modules/moderation/src/Events/ActionExecuted.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Events;

use He4rt\Moderation\Models\ModerationAction;
use Illuminate\Foundation\Events\Dispatchable;

final class ActionExecuted
{
    use Dispatchable;

    public function __construct(public readonly ModerationAction $action) {}
}
```

- [ ] **Step 2: Create RecordAuditLog listener**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Listeners;

use He4rt\Moderation\Events\ActionExecuted;
use He4rt\Moderation\Events\CaseCreated;
use He4rt\Moderation\Events\CaseResolved;
use He4rt\Moderation\Models\ModerationAuditLog;

final class RecordAuditLog
{
    public function handleCaseCreated(CaseCreated $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'case_created',
            'actor_id' => null,
            'actor_type' => 'system',
            'case_id' => $event->case->id,
            'details' => [
                'source' => $event->case->source->value,
                'platform' => $event->case->source_platform->value,
                'content_type' => $event->case->content_type,
            ],
            'platform' => $event->case->source_platform->value,
            'tenant_id' => $event->case->tenant_id,
        ]);
    }

    public function handleCaseResolved(CaseResolved $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'case_resolved',
            'actor_id' => $event->case->assigned_to,
            'actor_type' => 'moderator',
            'case_id' => $event->case->id,
            'details' => [
                'status' => $event->case->status->value,
                'violation_type' => $event->case->violation_type?->value,
            ],
            'platform' => $event->case->source_platform->value,
            'tenant_id' => $event->case->tenant_id,
        ]);
    }

    public function handleActionExecuted(ActionExecuted $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'action_executed',
            'actor_id' => $event->action->moderator_id,
            'actor_type' => $event->action->automated ? 'system' : 'moderator',
            'case_id' => $event->action->case_id,
            'details' => [
                'action_type' => $event->action->action_type->value,
                'target_platforms' => $event->action->target_platforms,
                'duration' => $event->action->duration,
                'execution_results' => $event->action->execution_results,
            ],
            'platform' => null,
            'tenant_id' => $event->action->case?->tenant_id,
        ]);
    }
}
```

- [ ] **Step 3: Register events in ModerationServiceProvider**

Update `ModerationServiceProvider::boot()`:

```php
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

    Event::listen(CaseCreated::class, [RecordAuditLog::class, 'handleCaseCreated']);
    Event::listen(CaseResolved::class, [RecordAuditLog::class, 'handleCaseResolved']);
    Event::listen(ActionExecuted::class, [RecordAuditLog::class, 'handleActionExecuted']);
}
```

Add imports:

```php
use He4rt\Moderation\Events\ActionExecuted;
use He4rt\Moderation\Events\CaseCreated;
use He4rt\Moderation\Events\CaseResolved;
use He4rt\Moderation\Listeners\RecordAuditLog;
use Illuminate\Support\Facades\Event;
```

- [ ] **Step 4: Commit**

```bash
git add app-modules/moderation/src/Events app-modules/moderation/src/Listeners app-modules/moderation/src/ModerationServiceProvider.php
git commit -m "feat(moderation): add events, audit log listener, and event registration"
```

---

## Task 11: Web Platform Adapter

**Files:**

- Create: `app-modules/moderation/src/Adapters/WebModerationAdapter.php`
- Test: `app-modules/moderation/tests/Feature/WebAdapterTest.php`

- [ ] **Step 1: Write Web adapter test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Adapters\WebModerationAdapter;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('WebModerationAdapter returns Web platform', function (): void {
    $adapter = new WebModerationAdapter();
    expect($adapter->platform())->toBe(Platform::Web);
});

test('WebModerationAdapter supports correct action types', function (): void {
    $adapter = new WebModerationAdapter();
    $supported = $adapter->supports();

    expect($supported)
        ->toContain(ActionType::Warn)
        ->and($supported)
        ->toContain(ActionType::Suspend)
        ->and($supported)
        ->toContain(ActionType::Ban)
        ->and($supported)
        ->toContain(ActionType::ContentRemove)
        ->and($supported)
        ->not->toContain(ActionType::Kick);
});

test('WebModerationAdapter executes suspend action', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'duration' => '7d',
        'target_platforms' => ['web'],
    ]);

    $adapter = new WebModerationAdapter();
    $result = $adapter->execute($action, $user);

    expect($result->platform)->toBe(Platform::Web)->and($result->success)->toBeTrue();

    $user->refresh();
    expect($user->suspended_until)->not->toBeNull();
});

test('WebModerationAdapter executes ban action', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'ban',
        'duration' => 'permanent',
        'target_platforms' => ['web'],
    ]);

    $adapter = new WebModerationAdapter();
    $result = $adapter->execute($action, $user);

    expect($result->success)->toBeTrue();

    $user->refresh();
    expect($user->banned_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=WebAdapterTest`
Expected: FAIL

- [ ] **Step 3: Create WebModerationAdapter**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Adapters;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\ModerationPlatformContract;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Models\ModerationAction;

final class WebModerationAdapter implements ModerationPlatformContract
{
    public function platform(): Platform
    {
        return Platform::Web;
    }

    public function ingest(array $rawPayload): ModerationContentDTO
    {
        return new ModerationContentDTO(
            contentId: $rawPayload['content_id'] ?? '',
            contentType: $rawPayload['content_type'] ?? 'post',
            sourcePlatform: Platform::Web,
            authorExternalId: $rawPayload['author_id'] ?? '',
            author: null,
            textContent: $rawPayload['text'] ?? '',
            mediaUrls: $rawPayload['media_urls'] ?? [],
            metadata: $rawPayload['metadata'] ?? [],
            snapshot: $rawPayload,
            tenantId: $rawPayload['tenant_id'] ?? null,
        );
    }

    public function execute(ModerationAction $action, User $target): ExecutionResultDTO
    {
        try {
            match ($action->action_type) {
                ActionType::Warn => $this->executeWarn($target),
                ActionType::Suspend => $this->executeSuspend($target, $action->duration),
                ActionType::Ban => $this->executeBan($target),
                ActionType::ContentRemove => $this->executeContentRemove($action),
                default => null,
            };

            return new ExecutionResultDTO(
                platform: Platform::Web,
                success: true,
                error: null,
                platformResponse: ['action' => $action->action_type->value],
            );
        } catch (\Throwable $e) {
            return new ExecutionResultDTO(
                platform: Platform::Web,
                success: false,
                error: $e->getMessage(),
                platformResponse: [],
            );
        }
    }

    public function notify(User $user, string $message, array $context = []): void
    {
        // Web notifications will be implemented via Laravel notifications
    }

    /** @return array<ActionType> */
    public function supports(): array
    {
        return [ActionType::Warn, ActionType::Suspend, ActionType::Ban, ActionType::ContentRemove];
    }

    public function resolveUser(string $externalId): ?User
    {
        return User::query()->find($externalId);
    }

    private function executeWarn(User $target): void
    {
        // Warn is recorded in the moderation system, no DB change to user
    }

    private function executeSuspend(User $target, ?string $duration): void
    {
        $until = match ($duration) {
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '24h' => now()->addHours(24),
            default => now()->addDays(7),
        };

        $target->update(['suspended_until' => $until]);
    }

    private function executeBan(User $target): void
    {
        $target->update(['banned_at' => now()]);
    }

    private function executeContentRemove(ModerationAction $action): void
    {
        // Soft-delete the content identified by the case's content_id/content_type
    }
}
```

- [ ] **Step 4: Register adapter in ModerationServiceProvider**

Add to `register()`:

```php
public function register(): void
{
    $this->mergeConfigFrom(__DIR__.'/../config/moderation.php', 'moderation');

    $this->app->singleton(Adapters\WebModerationAdapter::class);
    $this->app->tag([Adapters\WebModerationAdapter::class], 'moderation.platforms');
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=WebAdapterTest`
Expected: PASS (4 tests)

Note: The `suspended_until` and `banned_at` columns may need to be added to the users table. If they don't exist, create a migration:

```php
Schema::table('users', function (Blueprint $table): void {
    $table->timestampTz('suspended_until')->nullable();
    $table->timestampTz('banned_at')->nullable();
});
```

- [ ] **Step 6: Commit**

```bash
git add app-modules/moderation/src/Adapters app-modules/moderation/src/ModerationServiceProvider.php app-modules/moderation/tests/Feature/WebAdapterTest.php
git commit -m "feat(moderation): add web platform adapter with suspend/ban execution"
```

---

## Task 12: Filament Resources — ModerationCaseResource

**Files:**

- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationCaseResource.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationCaseResource/Pages/ListModerationCases.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationCaseResource/Pages/ViewModerationCase.php`
- Modify: `app-modules/panel-admin/config/panel-admin.php` — add 'moderation' to modules array
- Test: `app-modules/moderation/tests/Feature/FilamentResourceTest.php`

- [ ] **Step 1: Register moderation module in panel-admin config**

In `app-modules/panel-admin/config/panel-admin.php`, add `'moderation'` to the modules array:

```php
'modules' => [
    'moderation',
],
```

- [ ] **Step 2: Create ModerationCaseResource**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Filament\Admin\Resources\ModerationCaseResource\Pages;
use He4rt\Moderation\Models\ModerationCase;

class ModerationCaseResource extends Resource
{
    protected static ?string $model = ModerationCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Moderation Queue';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(
                        fn(int $state) => match (true) {
                            $state >= 90 => 'danger',
                            $state >= 70 => 'warning',
                            default => 'gray',
                        },
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('violation_type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('source_platform')->badge()->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('Author')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(
                    fn(CaseStatus $state) => match ($state) {
                        CaseStatus::Pending => 'warning',
                        CaseStatus::Assigned => 'info',
                        CaseStatus::Resolved => 'success',
                        CaseStatus::Escalated => 'danger',
                        CaseStatus::Dismissed => 'gray',
                    },
                ),
                Tables\Columns\TextColumn::make('severity')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CaseStatus::class),
                Tables\Filters\SelectFilter::make('source_platform')->options(Platform::class),
                Tables\Filters\SelectFilter::make('violation_type')->options(ViolationType::class),
                Tables\Filters\SelectFilter::make('severity')->options(Severity::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationCases::route('/'),
            'view' => Pages\ViewModerationCase::route('/{record}'),
        ];
    }
}
```

- [ ] **Step 3: Create ListModerationCases page**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationCaseResource\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\Moderation\Filament\Admin\Resources\ModerationCaseResource;

class ListModerationCases extends ListRecords
{
    protected static string $resource = ModerationCaseResource::class;

    protected ?string $heading = 'Moderation Queue';
}
```

- [ ] **Step 4: Create ViewModerationCase page**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationCaseResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Filament\Admin\Resources\ModerationCaseResource;
use He4rt\Moderation\Jobs\ExecuteAction;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationCase;

class ViewModerationCase extends ViewRecord
{
    protected static string $resource = ModerationCaseResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Content')->schema([
                Infolists\Components\TextEntry::make('content_snapshot.text')->label('Content Text')->columnSpanFull(),
                Infolists\Components\TextEntry::make('source_platform')->badge(),
                Infolists\Components\TextEntry::make('content_type'),
            ]),
            Infolists\Components\Section::make('Classification')->schema([
                Infolists\Components\TextEntry::make('violation_type')->badge(),
                Infolists\Components\TextEntry::make('severity')->badge(),
                Infolists\Components\TextEntry::make('ai_scores')
                    ->label('AI Scores')
                    ->formatStateUsing(
                        fn($state) => collect($state)
                            ->map(fn($score, $type) => "{$type}: " . number_format($score, 2))
                            ->implode(', '),
                    ),
                Infolists\Components\TextEntry::make('suggested_action')->badge()->color('warning'),
            ]),
            Infolists\Components\Section::make('Author')->schema([
                Infolists\Components\TextEntry::make('author.name'),
                Infolists\Components\TextEntry::make('priority')->badge(),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('take_action')
                ->label('Take Action')
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->visible(
                    fn() => $this->record->status === CaseStatus::Pending ||
                        $this->record->status === CaseStatus::Assigned,
                )
                ->form([
                    Forms\Components\Select::make('action_type')->options(ActionType::class)->required(),
                    Forms\Components\Select::make('duration')->options([
                        '24h' => '24 hours',
                        '7d' => '7 days',
                        '30d' => '30 days',
                        'permanent' => 'Permanent',
                    ]),
                    Forms\Components\CheckboxList::make('target_platforms')->options(Platform::class)->required(),
                    Forms\Components\Textarea::make('reason')->required(),
                ])
                ->action(function (array $data): void {
                    /** @var ModerationCase $case */
                    $case = $this->record;

                    $action = ModerationAction::query()->create([
                        'case_id' => $case->id,
                        'moderator_id' => auth()->id(),
                        'action_type' => $data['action_type'],
                        'target_platforms' => $data['target_platforms'],
                        'duration' => $data['duration'],
                        'reason' => $data['reason'],
                        'automated' => false,
                    ]);

                    if ($case->author) {
                        dispatch_sync(new ExecuteAction($action, $case->author));
                    }
                }),

            Actions\Action::make('dismiss')
                ->label('Dismiss')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(
                    fn() => $this->record->status === CaseStatus::Pending ||
                        $this->record->status === CaseStatus::Assigned,
                )
                ->requiresConfirmation()
                ->action(
                    fn() => $this->record->update([
                        'status' => CaseStatus::Dismissed,
                        'resolved_at' => now(),
                    ]),
                ),
        ];
    }
}
```

- [ ] **Step 5: Write Filament resource test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('moderation case list page renders', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin/moderation-cases')
        ->assertSuccessful();
});

test('moderation case list shows cases', function (): void {
    $case = ModerationCase::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/admin/moderation-cases')
        ->assertSuccessful()
        ->assertSee($case->violation_type->value);
});
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=FilamentResourceTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app-modules/moderation/src/Filament app-modules/panel-admin/config/panel-admin.php app-modules/moderation/tests/Feature/FilamentResourceTest.php
git commit -m "feat(moderation): add Filament ModerationCaseResource with list and view pages"
```

---

## Task 13: Filament Resources — ModerationRuleResource & ModerationAppealResource

**Files:**

- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationRuleResource.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationRuleResource/Pages/ListModerationRules.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationRuleResource/Pages/CreateModerationRule.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationRuleResource/Pages/EditModerationRule.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationAppealResource.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationAppealResource/Pages/ListModerationAppeals.php`
- Create: `app-modules/moderation/src/Filament/Admin/Resources/ModerationAppealResource/Pages/ViewModerationAppeal.php`

- [ ] **Step 1: Create ModerationRuleResource with full CRUD**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource\Pages;
use He4rt\Moderation\Models\ModerationRule;

class ModerationRuleResource extends Resource
{
    protected static ?string $model = ModerationRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Rules';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(100),
            Forms\Components\Select::make('type')
                ->options(['keyword' => 'Keyword', 'regex' => 'Regex'])
                ->required(),
            Forms\Components\Textarea::make('pattern')
                ->required()
                ->helperText('Keywords: comma-separated. Regex: pattern without delimiters.'),
            Forms\Components\Select::make('platform')->options(Platform::class)->placeholder('All platforms'),
            Forms\Components\Select::make('violation_type')->options(ViolationType::class)->required(),
            Forms\Components\Select::make('severity')->options(Severity::class)->required(),
            Forms\Components\Select::make('action_on_match')->options(ActionType::class)->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('platform')->badge()->default('All'),
                Tables\Columns\TextColumn::make('violation_type')->badge(),
                Tables\Columns\TextColumn::make('severity')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationRules::route('/'),
            'create' => Pages\CreateModerationRule::route('/create'),
            'edit' => Pages\EditModerationRule::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2: Create Rule pages (List, Create, Edit)**

`ListModerationRules.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource;

class ListModerationRules extends ListRecords
{
    protected static string $resource = ModerationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
```

`CreateModerationRule.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource;

class CreateModerationRule extends CreateRecord
{
    protected static string $resource = ModerationRuleResource::class;
}
```

`EditModerationRule.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use He4rt\Moderation\Filament\Admin\Resources\ModerationRuleResource;

class EditModerationRule extends EditRecord
{
    protected static string $resource = ModerationRuleResource::class;
}
```

- [ ] **Step 3: Create ModerationAppealResource**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Filament\Admin\Resources\ModerationAppealResource\Pages;
use He4rt\Moderation\Models\ModerationAppeal;

class ModerationAppealResource extends Resource
{
    protected static ?string $model = ModerationAppeal::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Appeals';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sla_deadline', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('status')->badge()->color(
                    fn(AppealStatus $state) => match ($state) {
                        AppealStatus::Pending => 'warning',
                        AppealStatus::Reviewing => 'info',
                        AppealStatus::Upheld => 'danger',
                        AppealStatus::Overturned => 'success',
                    },
                ),
                Tables\Columns\TextColumn::make('appellant.name')->label('Appellant'),
                Tables\Columns\TextColumn::make('reason_category')->badge(),
                Tables\Columns\TextColumn::make('action.action_type')->label('Original Action')->badge(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('Reviewer'),
                Tables\Columns\TextColumn::make('sla_deadline')
                    ->dateTime()
                    ->color(fn($state) => $state && now()->gt($state) ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('status')->options(AppealStatus::class)]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModerationAppeals::route('/'),
            'view' => Pages\ViewModerationAppeal::route('/{record}'),
        ];
    }
}
```

- [ ] **Step 4: Create Appeal pages**

`ListModerationAppeals.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationAppealResource\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\Moderation\Filament\Admin\Resources\ModerationAppealResource;

class ListModerationAppeals extends ListRecords
{
    protected static string $resource = ModerationAppealResource::class;

    protected ?string $heading = 'Appeals Queue';
}
```

`ViewModerationAppeal.php`:

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Resources\ModerationAppealResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Filament\Admin\Resources\ModerationAppealResource;

class ViewModerationAppeal extends ViewRecord
{
    protected static string $resource = ModerationAppealResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Appeal Details')->schema([
                Infolists\Components\TextEntry::make('reason_category')->badge(),
                Infolists\Components\TextEntry::make('reason_text')->columnSpanFull(),
                Infolists\Components\TextEntry::make('appellant.name'),
                Infolists\Components\TextEntry::make('sla_deadline')->dateTime(),
            ]),
            Infolists\Components\Section::make('Original Action')->schema([
                Infolists\Components\TextEntry::make('action.action_type')->badge(),
                Infolists\Components\TextEntry::make('action.reason'),
                Infolists\Components\TextEntry::make('action.moderator.name')->label('Original Moderator'),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uphold')
                ->label('Uphold Decision')
                ->icon('heroicon-o-hand-thumb-down')
                ->color('danger')
                ->visible(fn() => in_array($this->record->status, [AppealStatus::Pending, AppealStatus::Reviewing]))
                ->form([Forms\Components\Textarea::make('reviewer_notes')->required()])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => AppealStatus::Upheld,
                        'reviewer_id' => auth()->id(),
                        'reviewer_notes' => $data['reviewer_notes'],
                        'resolved_at' => now(),
                    ]);
                }),

            Actions\Action::make('overturn')
                ->label('Overturn Decision')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->visible(fn() => in_array($this->record->status, [AppealStatus::Pending, AppealStatus::Reviewing]))
                ->form([Forms\Components\Textarea::make('reviewer_notes')->required()])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => AppealStatus::Overturned,
                        'reviewer_id' => auth()->id(),
                        'reviewer_notes' => $data['reviewer_notes'],
                        'resolved_at' => now(),
                    ]);
                    // TODO: Reverse the action on platforms (future task)
                }),
        ];
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/Filament
git commit -m "feat(moderation): add ModerationRuleResource and ModerationAppealResource"
```

---

## Task 14: Moderation Dashboard Page & Widgets

**Files:**

- Create: `app-modules/moderation/src/Filament/Admin/Pages/ModerationDashboard.php`
- Create: `app-modules/moderation/src/Filament/Admin/Widgets/CasesByStatusWidget.php`
- Create: `app-modules/moderation/src/Filament/Admin/Widgets/RecentActionsWidget.php`

- [ ] **Step 1: Create ModerationDashboard page**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Pages;

use Filament\Pages\Page;
use He4rt\Moderation\Filament\Admin\Widgets\CasesByStatusWidget;
use He4rt\Moderation\Filament\Admin\Widgets\RecentActionsWidget;

class ModerationDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'panel-admin::filament-page';

    public function getHeaderWidgets(): array
    {
        return [CasesByStatusWidget::class, RecentActionsWidget::class];
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 2;
    }
}
```

- [ ] **Step 2: Create CasesByStatusWidget**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Models\ModerationCase;

class CasesByStatusWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending', ModerationCase::query()->where('status', 'pending')->count())
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make(
                'Resolved (this month)',
                ModerationCase::query()
                    ->where('status', 'resolved')
                    ->where('resolved_at', '>=', now()->startOfMonth())
                    ->count(),
            )
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Escalated', ModerationCase::query()->where('status', 'escalated')->count())
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
```

- [ ] **Step 3: Create RecentActionsWidget**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Models\ModerationAction;

class RecentActionsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Actions';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ModerationAction::query()->latest('created_at')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('action_type')->badge(),
                Tables\Columns\TextColumn::make('moderator.name')->label('Moderator'),
                Tables\Columns\TextColumn::make('reason')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->paginated(false);
    }
}
```

- [ ] **Step 4: Create a minimal Blade view for the dashboard page**

Create `app-modules/panel-admin/resources/views/filament-page.blade.php`:

```blade
<x-filament-panels::page>
    @if (method_exists($this, 'getHeaderWidgets'))
        <x-filament-widgets::widgets :widgets="$this->getHeaderWidgets()" :columns="$this->getHeaderWidgetsColumns()" />
    @endif
</x-filament-panels::page>
```

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/Filament/Admin/Pages app-modules/moderation/src/Filament/Admin/Widgets app-modules/panel-admin/resources/views/filament-page.blade.php
git commit -m "feat(moderation): add moderation dashboard with stats and recent actions widgets"
```

---

## Task 15: Integration — Wire Up Full Pipeline Dispatch

**Files:**

- Create: `app-modules/moderation/src/Actions/CreateCase.php`
- Create: `app-modules/moderation/src/Actions/SubmitReport.php`
- Test: `app-modules/moderation/tests/Feature/CaseWorkflowTest.php`

- [ ] **Step 1: Write case workflow test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Actions\SubmitReport;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('SubmitReport creates case and runs pipeline', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => [], 'category_scores' => ['harassment' => 0.01]]],
        ]),
    ]);

    $reporter = User::factory()->create();
    $author = User::factory()->create();

    $action = app(SubmitReport::class);
    $case = $action->handle(
        reporter: $reporter,
        contentDTO: new ModerationContentDTO(
            contentId: 'msg-report-test',
            contentType: 'message',
            sourcePlatform: Platform::Discord,
            authorExternalId: 'ext-id',
            author: $author,
            textContent: 'some bad content',
            mediaUrls: [],
            metadata: [],
            snapshot: ['text' => 'some bad content'],
            tenantId: null,
        ),
        reason: ViolationType::Toxicity,
        details: 'This user is being toxic',
        platform: Platform::Discord,
    );

    expect($case)
        ->toBeInstanceOf(ModerationCase::class)
        ->and($case->source)
        ->toBe(CaseSource::UserReport)
        ->and($case->ai_scores)
        ->not->toBeNull();

    $this->assertDatabaseHas('moderation_reports', [
        'case_id' => $case->id,
        'reporter_id' => $reporter->id,
        'reason' => 'toxicity',
    ]);
});

test('SubmitReport deduplicates reports to same content', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => [], 'category_scores' => []]],
        ]),
    ]);

    $author = User::factory()->create();
    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();

    $action = app(SubmitReport::class);

    $dto = new ModerationContentDTO(
        contentId: 'msg-dedup',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-id',
        author: $author,
        textContent: 'bad stuff',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'bad stuff'],
        tenantId: null,
    );

    $case1 = $action->handle($reporter1, $dto, ViolationType::Spam, null, Platform::Discord);
    $case2 = $action->handle($reporter2, $dto, ViolationType::Spam, null, Platform::Discord);

    expect($case1->id)->toBe($case2->id);
    expect(ModerationReport::query()->where('case_id', $case1->id)->count())->toBe(2);

    $case1->refresh();
    expect($case1->priority)->toBeGreaterThan(50);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CaseWorkflowTest`
Expected: FAIL

- [ ] **Step 3: Create SubmitReport action**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Jobs\ClassifyContent;
use He4rt\Moderation\Jobs\IngestContent;
use He4rt\Moderation\Jobs\RouteDecision;
use He4rt\Moderation\Models\ModerationCase;
use He4rt\Moderation\Models\ModerationReport;

final readonly class SubmitReport
{
    public function handle(
        User $reporter,
        ModerationContentDTO $contentDTO,
        ViolationType $reason,
        ?string $details,
        Platform $platform,
    ): ModerationCase {
        $existingCase = ModerationCase::query()
            ->where('content_id', $contentDTO->contentId)
            ->where('content_type', $contentDTO->contentType)
            ->whereIn('status', [CaseStatus::Pending->value, CaseStatus::Assigned->value])
            ->first();

        if ($existingCase) {
            $this->addReport($existingCase, $reporter, $reason, $details, $platform);
            $existingCase->increment('priority', 10);

            return $existingCase;
        }

        $case = new IngestContent($contentDTO, CaseSource::UserReport)->handle();
        $this->addReport($case, $reporter, $reason, $details, $platform);

        new ClassifyContent($case)->handle();
        $case->refresh();
        new RouteDecision($case)->handle();

        return $case->refresh();
    }

    private function addReport(
        ModerationCase $case,
        User $reporter,
        ViolationType $reason,
        ?string $details,
        Platform $platform,
    ): void {
        ModerationReport::query()->create([
            'case_id' => $case->id,
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
            'platform' => $platform,
        ]);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=CaseWorkflowTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add app-modules/moderation/src/Actions app-modules/moderation/tests/Feature/CaseWorkflowTest.php
git commit -m "feat(moderation): add SubmitReport action with dedup and full pipeline dispatch"
```

---

## Task 16: Appeal Workflow Action

**Files:**

- Create: `app-modules/moderation/src/Actions/FileAppeal.php`
- Create: `app-modules/moderation/src/Actions/ReviewAppeal.php`
- Test: `app-modules/moderation/tests/Feature/AppealWorkflowTest.php`

- [ ] **Step 1: Write appeal workflow test**

```php
<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Actions\FileAppeal;
use He4rt\Moderation\Actions\ReviewAppeal;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;
use He4rt\Moderation\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('FileAppeal creates appeal with SLA deadline', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);

    $fileAppeal = app(FileAppeal::class);
    $appeal = $fileAppeal->handle(
        action: $action,
        appellant: $user,
        reasonCategory: 'context_misunderstood',
        reasonText: 'I was being sarcastic',
    );

    expect($appeal)
        ->toBeInstanceOf(ModerationAppeal::class)
        ->and($appeal->status)
        ->toBe(AppealStatus::Pending)
        ->and($appeal->sla_deadline)
        ->not->toBeNull()
        ->and($appeal->appellant_id)
        ->toBe($user->id);
});

test('FileAppeal rejects if outside window', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'created_at' => now()->subDays(10),
    ]);

    $fileAppeal = app(FileAppeal::class);

    expect(fn() => $fileAppeal->handle($action, $user, 'other', 'too late'))->toThrow(
        \DomainException::class,
        'Appeal window has expired',
    );
});

test('FileAppeal rejects duplicate appeal', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);
    ModerationAppeal::factory()->create(['action_id' => $action->id, 'appellant_id' => $user->id]);

    $fileAppeal = app(FileAppeal::class);

    expect(fn() => $fileAppeal->handle($action, $user, 'other', 'again'))->toThrow(
        \DomainException::class,
        'Appeal already exists',
    );
});

test('ReviewAppeal upholds decision', function (): void {
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => User::factory()->create()->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    $reviewAppeal = app(ReviewAppeal::class);
    $reviewAppeal->handle($appeal, $reviewer, AppealStatus::Upheld, 'Decision was correct');

    $appeal->refresh();
    expect($appeal->status)
        ->toBe(AppealStatus::Upheld)
        ->and($appeal->reviewer_id)
        ->toBe($reviewer->id)
        ->and($appeal->reviewer_notes)
        ->toBe('Decision was correct')
        ->and($appeal->resolved_at)
        ->not->toBeNull();
});

test('ReviewAppeal rejects same moderator as reviewer', function (): void {
    $moderator = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    $reviewAppeal = app(ReviewAppeal::class);

    expect(fn() => $reviewAppeal->handle($appeal, $moderator, AppealStatus::Upheld, 'notes'))->toThrow(
        \DomainException::class,
        'Reviewer must be different',
    );
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AppealWorkflowTest`
Expected: FAIL

- [ ] **Step 3: Create FileAppeal action**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAction;
use He4rt\Moderation\Models\ModerationAppeal;

final readonly class FileAppeal
{
    public function handle(
        ModerationAction $action,
        User $appellant,
        string $reasonCategory,
        ?string $reasonText,
    ): ModerationAppeal {
        $windowDays = config('moderation.appeals.window_days', 7);
        $slaHours = config('moderation.appeals.sla_hours', 48);

        if ($action->created_at->diffInDays(now()) > $windowDays) {
            throw new \DomainException('Appeal window has expired');
        }

        if ($action->appeal()->exists()) {
            throw new \DomainException('Appeal already exists for this action');
        }

        return ModerationAppeal::query()->create([
            'action_id' => $action->id,
            'appellant_id' => $appellant->id,
            'reason_category' => $reasonCategory,
            'reason_text' => $reasonText,
            'status' => AppealStatus::Pending,
            'sla_deadline' => now()->addHours($slaHours),
        ]);
    }
}
```

- [ ] **Step 4: Create ReviewAppeal action**

```php
<?php

declare(strict_types=1);

namespace He4rt\Moderation\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAppeal;

final readonly class ReviewAppeal
{
    public function handle(ModerationAppeal $appeal, User $reviewer, AppealStatus $decision, string $notes): void
    {
        $originalModeratorId = $appeal->action->moderator_id;

        if ($reviewer->id === $originalModeratorId) {
            throw new \DomainException('Reviewer must be different from original moderator');
        }

        $appeal->update([
            'status' => $decision,
            'reviewer_id' => $reviewer->id,
            'reviewer_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=AppealWorkflowTest`
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add app-modules/moderation/src/Actions/FileAppeal.php app-modules/moderation/src/Actions/ReviewAppeal.php app-modules/moderation/tests/Feature/AppealWorkflowTest.php
git commit -m "feat(moderation): add FileAppeal and ReviewAppeal actions with validation"
```

---

## Task 17: Final Integration — Run All Tests & Verify

- [ ] **Step 1: Run full test suite**

Run: `php artisan test --filter=Moderation --compact`
Expected: All tests pass (Unit + Feature)

- [ ] **Step 2: Run Pint for code style**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No style violations (or auto-fixed)

- [ ] **Step 3: Run migrations fresh to verify schema**

Run: `php artisan migrate:fresh --seed`
Expected: All migrations apply cleanly

- [ ] **Step 4: Verify Filament panel loads**

Run: `php artisan serve` and visit `/admin`
Verify: Moderation navigation group appears with Dashboard, Queue, Appeals, Rules

- [ ] **Step 5: Final commit (if any pint fixes)**

```bash
git add -A
git commit -m "chore(moderation): apply code style fixes"
```

---

## Verification Checklist

- [ ] All moderation module tests pass (`php artisan test --filter=Moderation`)
- [ ] Migrations apply without error
- [ ] Filament admin panel shows: ModerationDashboard, ModerationCaseResource, ModerationAppealResource, ModerationRuleResource
- [ ] Create a ModerationCase via factory in tinker → appears in queue list
- [ ] SubmitReport action runs full pipeline (ingest → classify → route)
- [ ] FileAppeal validates window and deduplication
- [ ] ReviewAppeal enforces different-reviewer constraint
- [ ] Audit log records events (check `moderation_audit_log` table)
- [ ] WebModerationAdapter executes suspend/ban on User model
- [ ] Pint passes with no violations
