<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\RouteCaseAction;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('priority is calculated from max score', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.75, 'toxicity' => 0.3],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
        'suggested_action' => 'ban',
    ]);

    $action = resolve(RouteCaseAction::class);
    $action->execute($case);

    $case->refresh();

    expect($case->priority)->toBe(75)
        ->and($case->status)->toBe(CaseStatus::Pending);
});

test('high priority boost is applied when score exceeds threshold', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.95],
        'violation_type' => 'spam',
        'severity' => 'critical',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
        'suggested_action' => 'ban',
    ]);

    $action = resolve(RouteCaseAction::class);
    $action->execute($case);

    $case->refresh();

    // 95 + 10 = 105, capped at 100
    expect($case->priority)->toBe(100);
});

test('report count boosts priority', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.75],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
        'suggested_action' => 'ban',
    ]);

    // Create 2 reports for boost
    $case->reports()->create(['reporter_id' => User::factory()->create()->id, 'reason' => 'spam', 'platform' => 'discord']);
    $case->reports()->create(['reporter_id' => User::factory()->create()->id, 'reason' => 'spam', 'platform' => 'discord']);

    $action = resolve(RouteCaseAction::class);
    $action->execute($case);

    $case->refresh();

    // 75 (base) + 20 (2 reports * 10) = 95
    expect($case->priority)->toBe(95);
});

test('penalty advisor is consulted when suggested_action is null and conditions are met', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.8],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
        'suggested_action' => null,
    ]);

    $action = resolve(RouteCaseAction::class);
    $action->execute($case);

    $case->refresh();

    expect($case->suggested_action)->not->toBeNull();
});

test('penalty advisor is NOT consulted when suggested_action already set', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.8],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
        'suggested_action' => 'warn',
    ]);

    $action = resolve(RouteCaseAction::class);
    $action->execute($case);

    $case->refresh();

    // suggested_action should remain 'warn' (advisor was not consulted)
    expect($case->suggested_action->value)->toBe('warn');
});
