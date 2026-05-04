<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('suggests warn for first offense low severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->action)->toBe(ActionType::Warn)
        ->and($result->duration)->toBeNull()
        ->and($result->priorOffenses)->toBe(0);
});

test('suggests warn for first offense medium severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)->toBe(ActionType::Warn)
        ->and($result->duration)->toBeNull();
});

test('suggests mute 24h for first offense high severity', function (): void {
    $user = User::factory()->create();
    $advisor = new HistoryBasedPenaltyAdvisor();

    $result = $advisor->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('24h');
});

test('suggests mute 24h for second offense medium severity', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(5)]);

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('24h')
        ->and($result->priorOffenses)->toBe(1);
});

test('suggests ban 7d for third offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 2; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(10 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Medium);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('7d')
        ->and($result->priorOffenses)->toBe(2);
});

test('suggests ban 30d for fourth offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(15 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('30d')
        ->and($result->priorOffenses)->toBe(3);
});

test('suggests permanent ban for fifth offense', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(20 - $i)]);
    }

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('permanent')
        ->and($result->priorOffenses)->toBe(5);
});

test('only counts offenses within escalation window', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(45)]);

    $advisor = new HistoryBasedPenaltyAdvisor();
    $result = $advisor->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->priorOffenses)->toBe(0)
        ->and($result->action)->toBe(ActionType::Warn);
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

    expect($result->history)->toHaveCount(1)
        ->and($result->reasoning)->toContain('1');
});
