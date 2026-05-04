<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Audit\ModerationAuditLog;
use He4rt\Moderation\Audit\RecordAuditLog;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Events\CaseResolved;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ActionExecuted;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('records audit log on CaseCreated event', function (): void {
    $case = ModerationCase::factory()->create();

    $listener = new RecordAuditLog();
    $listener->handleCaseCreated(new CaseCreated($case));

    $log = ModerationAuditLog::query()->where('event_type', 'case_created')->first();
    expect($log)->not->toBeNull()
        ->and($log->case_id)->toBe($case->id)
        ->and($log->actor_type)->toBe('system')
        ->and($log->details['source'])->toBe($case->source->value)
        ->and($log->details['platform'])->toBe($case->source_platform->value);
});

test('records audit log on CaseResolved event', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'status' => CaseStatus::Resolved,
        'assigned_to' => $moderator->id,
    ]);

    $listener = new RecordAuditLog();
    $listener->handleCaseResolved(new CaseResolved($case));

    $log = ModerationAuditLog::query()->where('event_type', 'case_resolved')->first();
    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($moderator->id)
        ->and($log->actor_type)->toBe('moderator')
        ->and($log->details['status'])->toBe('resolved');
});

test('records audit log on ActionExecuted event', function (): void {
    $moderator = User::factory()->create();
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'moderator_id' => $moderator->id,
        'action_type' => 'ban',
        'target_platforms' => ['discord', 'web'],
        'duration' => '7d',
        'execution_results' => [['platform' => 'web', 'success' => true, 'error' => null]],
    ]);

    $listener = new RecordAuditLog();
    $listener->handleActionExecuted(new ActionExecuted($action));

    $log = ModerationAuditLog::query()->where('event_type', 'action_executed')->first();
    expect($log)->not->toBeNull()
        ->and($log->actor_id)->toBe($moderator->id)
        ->and($log->actor_type)->toBe('moderator')
        ->and($log->details['action_type'])->toBe('ban')
        ->and($log->details['target_platforms'])->toBe(['discord', 'web'])
        ->and($log->details['duration'])->toBe('7d');
});

test('ActionExecuted marks actor_type as system for automated actions', function (): void {
    $case = ModerationCase::factory()->create();
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'moderator_id' => null,
        'automated' => true,
        'action_type' => 'warn',
        'target_platforms' => ['web'],
    ]);

    $listener = new RecordAuditLog();
    $listener->handleActionExecuted(new ActionExecuted($action));

    $log = ModerationAuditLog::query()->where('event_type', 'action_executed')->first();
    expect($log->actor_type)->toBe('system')
        ->and($log->actor_id)->toBeNull();
});

test('audit log stores tenant_id from case', function (): void {
    $tenant = Tenant::factory()->create();
    $case = ModerationCase::factory()->create(['tenant_id' => $tenant->id]);

    $listener = new RecordAuditLog();
    $listener->handleCaseCreated(new CaseCreated($case));

    $log = ModerationAuditLog::query()->where('event_type', 'case_created')->first();
    expect((int) $log->tenant_id)->toBe($tenant->id);
});
