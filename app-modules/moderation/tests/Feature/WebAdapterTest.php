<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\WebModerationAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('WebModerationAdapter returns Web platform', function (): void {
    $adapter = new WebModerationAdapter();
    expect($adapter->platform())->toBe(Platform::Web);
});

test('WebModerationAdapter supports correct action types', function (): void {
    $adapter = new WebModerationAdapter();
    $supported = $adapter->supports();

    expect($supported)->toContain(ActionType::Warn)
        ->and($supported)->toContain(ActionType::Suspend)
        ->and($supported)->toContain(ActionType::Ban)
        ->and($supported)->toContain(ActionType::ContentRemove)
        ->and($supported)->not->toContain(ActionType::Kick);
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

    expect($result->platform)->toBe(Platform::Web)
        ->and($result->success)->toBeTrue();

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
