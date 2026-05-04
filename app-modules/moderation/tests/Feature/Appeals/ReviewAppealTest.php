<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Appeals\ReviewAppeal;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\AppealStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('upholds appeal and records reviewer', function (): void {
    $moderator = User::factory()->create();
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    resolve(ReviewAppeal::class)->handle($appeal, $reviewer, AppealStatus::Upheld, 'Decision was correct');

    $appeal->refresh();
    expect($appeal->status)->toBe(AppealStatus::Upheld)
        ->and($appeal->reviewer_id)->toBe($reviewer->id)
        ->and($appeal->reviewer_notes)->toBe('Decision was correct')
        ->and($appeal->resolved_at)->not->toBeNull();
});

test('overturns appeal and records reviewer', function (): void {
    $moderator = User::factory()->create();
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    resolve(ReviewAppeal::class)->handle($appeal, $reviewer, AppealStatus::Overturned, 'Context was valid');

    $appeal->refresh();
    expect($appeal->status)->toBe(AppealStatus::Overturned)
        ->and($appeal->reviewer_id)->toBe($reviewer->id)
        ->and($appeal->reviewer_notes)->toBe('Context was valid');
});

test('rejects when reviewer is the same moderator who took action', function (): void {
    $moderator = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    expect(fn () => resolve(ReviewAppeal::class)->handle($appeal, $moderator, AppealStatus::Upheld, 'notes'))
        ->toThrow(DomainException::class, 'Reviewer must be different');
});

test('allows review when original action had no moderator (automated)', function (): void {
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => null, 'automated' => true]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    resolve(ReviewAppeal::class)->handle($appeal, $reviewer, AppealStatus::Overturned, 'Auto-action was wrong');

    $appeal->refresh();
    expect($appeal->status)->toBe(AppealStatus::Overturned);
});

test('can review appeal that is in reviewing status', function (): void {
    $moderator = User::factory()->create();
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create([
        'action_id' => $action->id,
        'status' => AppealStatus::Reviewing,
    ]);

    resolve(ReviewAppeal::class)->handle($appeal, $reviewer, AppealStatus::Upheld, 'Confirmed');

    $appeal->refresh();
    expect($appeal->status)->toBe(AppealStatus::Upheld);
});
