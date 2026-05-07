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

// --- First offense (0 prior) ---

test('first offense low severity gives mute 24h', function (): void {
    $user = User::factory()->create();
    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('24h')
        ->and($result->priorOffenses)->toBe(0);
});

test('first offense medium severity gives mute 24h', function (): void {
    $user = User::factory()->create();
    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('24h');
});

test('first offense high severity gives ban 24h', function (): void {
    $user = User::factory()->create();
    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('24h');
});

test('first offense critical severity gives ban 24h', function (): void {
    $user = User::factory()->create();
    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Harassment, Severity::Critical);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('24h');
});

// --- Second offense (1 prior) ---

test('second offense medium severity gives mute 7d', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(5)]);

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('7d')
        ->and($result->priorOffenses)->toBe(1);
});

test('second offense high severity gives ban 7d', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(5)]);

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('7d')
        ->and($result->priorOffenses)->toBe(1);
});

// --- Third offense and beyond (2+ prior) ---

test('third offense medium severity gives mute 28d', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 2; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(10 - $i)]);
    }

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Spam, Severity::Medium);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('28d')
        ->and($result->priorOffenses)->toBe(2);
});

test('third offense high severity gives permanent ban', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 2; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(10 - $i)]);
    }

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Harassment, Severity::High);

    expect($result->action)->toBe(ActionType::Ban)
        ->and($result->duration)->toBe('permanent')
        ->and($result->priorOffenses)->toBe(2);
});

test('fourth offense medium severity still gives mute 28d', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 4; $i++) {
        $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
        ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(20 - $i)]);
    }

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Spam, Severity::Medium);

    expect($result->action)->toBe(ActionType::Mute)
        ->and($result->duration)->toBe('28d');
});

// --- Window and history ---

test('only counts offenses within escalation window', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id, 'status' => 'resolved']);
    ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()->subDays(45)]);

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Spam, Severity::Low);

    expect($result->priorOffenses)->toBe(0)
        ->and($result->duration)->toBe('24h');
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
        'action_type' => 'mute',
        'created_at' => now()->subDays(5),
    ]);

    $result = new HistoryBasedPenaltyAdvisor()->suggest($user, ViolationType::Toxicity, Severity::Medium);

    expect($result->history)->toHaveCount(1)
        ->and($result->reasoning)->toContain('1');
});
