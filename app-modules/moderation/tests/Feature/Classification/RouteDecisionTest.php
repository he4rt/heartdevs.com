<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Cases\Models\ModerationReport;
use He4rt\Moderation\Classification\Jobs\RouteDecision;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dismisses case when max score below flag threshold', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.2, 'toxicity' => 0.1],
        'status' => CaseStatus::Pending,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('dismisses case when scores are exactly at dismiss threshold', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.69],
        'status' => CaseStatus::Pending,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('flags case when score equals flag threshold', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.7],
        'violation_type' => 'spam',
        'severity' => 'medium',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Pending)
        ->and($case->priority)->toBeGreaterThanOrEqual(70);
});

test('sets high priority when score exceeds high priority threshold', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.95],
        'violation_type' => 'spam',
        'severity' => 'critical',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->priority)->toBeGreaterThanOrEqual(95);
});

test('boosts priority by 10 per existing report', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['toxicity' => 0.75],
        'violation_type' => 'toxicity',
        'severity' => 'medium',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    ModerationReport::factory()->count(3)->create(['case_id' => $case->id]);

    new RouteDecision($case)->handle();
    $case->refresh();

    // base 75 + report boost 30 = 105, capped at 100
    expect($case->priority)->toBe(100);
});

test('priority never exceeds 100', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.99],
        'violation_type' => 'spam',
        'severity' => 'critical',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    ModerationReport::factory()->count(5)->create(['case_id' => $case->id]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->priority)->toBeLessThanOrEqual(100);
});

test('suggests penalty when author and violation info available', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['harassment' => 0.85],
        'violation_type' => 'harassment',
        'severity' => 'high',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->suggested_action)->not->toBeNull();
});

test('does not suggest penalty when author is null', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.8],
        'violation_type' => 'spam',
        'severity' => 'medium',
        'status' => CaseStatus::Pending,
        'author_id' => null,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->suggested_action)->toBeNull()
        ->and($case->status)->toBe(CaseStatus::Pending);
});

test('handles empty scores array gracefully', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => [],
        'status' => CaseStatus::Pending,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('handles null scores gracefully', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => null,
        'status' => CaseStatus::Pending,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('keeps discord high severity cases open when ai scores are empty', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => [],
        'source_platform' => Platform::Discord,
        'severity' => 'critical',
        'status' => CaseStatus::Pending,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    expect($case->status)->toBe(CaseStatus::Pending);
});

test('escalates suggestion based on prior offenses', function (): void {
    $user = User::factory()->create();

    $priorCase = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => CaseStatus::Resolved]);
    ModerationAction::factory()->create(['case_id' => $priorCase->id, 'created_at' => now()->subDays(5)]);
    ModerationAction::factory()->create(['case_id' => $priorCase->id, 'created_at' => now()->subDays(3)]);

    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.8],
        'violation_type' => 'spam',
        'severity' => 'medium',
        'status' => CaseStatus::Pending,
        'author_id' => $user->id,
    ]);

    new RouteDecision($case)->handle();
    $case->refresh();

    // 2 prior offenses + medium severity → mute 28d (Discord timeout cap)
    expect($case->suggested_action)->toBe(ActionType::Mute);
});
