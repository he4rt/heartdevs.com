<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Audit\ModerationAuditLog;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
});

test('executes action on web platform and records results', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'target_platforms' => ['web'],
        'duration' => '7d',
    ]);

    new ExecuteAction($action, $user)->handle();

    $action->refresh();
    expect($action->execution_results)->not->toBeNull()
        ->and($action->execution_results[0]['platform'])->toBe('web')
        ->and($action->execution_results[0]['success'])->toBeTrue();

    $user->refresh();
    expect($user->suspended_until)->not->toBeNull();
});

test('resolves case after action execution', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => CaseStatus::Assigned]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'warn',
        'target_platforms' => ['web'],
    ]);

    new ExecuteAction($action, $user)->handle();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Resolved)
        ->and($case->resolved_at)->not->toBeNull();
});

test('records audit log entry after execution', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'ban',
        'target_platforms' => ['web'],
        'duration' => 'permanent',
        'moderator_id' => User::factory()->create()->id,
    ]);

    new ExecuteAction($action, $user)->handle();

    expect(ModerationAuditLog::query()->where('event_type', 'action_executed')->count())->toBe(1);

    $log = ModerationAuditLog::query()->where('event_type', 'action_executed')->first();
    expect($log->case_id)->toBe($case->id)
        ->and($log->actor_type)->toBe('moderator');
});

test('skips platforms not in target list', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'ban',
        'target_platforms' => ['discord'],
    ]);

    new ExecuteAction($action, $user)->handle();

    $action->refresh();
    expect($action->execution_results)->toBeEmpty();

    $user->refresh();
    expect($user->banned_at)->toBeNull();
});

test('handles multiple target platforms', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'ban',
        'target_platforms' => ['web', 'discord'],
    ]);

    new ExecuteAction($action, $user)->handle();

    $action->refresh();
    $webResult = collect($action->execution_results)->firstWhere('platform', 'web');
    expect($webResult)->not->toBeNull()
        ->and($webResult['success'])->toBeTrue();
});

test('handles empty target platforms array', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'warn',
        'target_platforms' => [],
    ]);

    new ExecuteAction($action, $user)->handle();

    $action->refresh();
    expect($action->execution_results)->toBeEmpty();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Resolved);
});
