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

    $fileAppeal = resolve(FileAppeal::class);
    $appeal = $fileAppeal->handle(
        action: $action,
        appellant: $user,
        reasonCategory: 'context_misunderstood',
        reasonText: 'I was being sarcastic',
    );

    expect($appeal)->toBeInstanceOf(ModerationAppeal::class)
        ->and($appeal->status)->toBe(AppealStatus::Pending)
        ->and($appeal->sla_deadline)->not->toBeNull()
        ->and($appeal->appellant_id)->toBe($user->id);
});

test('FileAppeal rejects if outside window', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'created_at' => now()->subDays(10),
    ]);

    $fileAppeal = resolve(FileAppeal::class);

    expect(fn () => $fileAppeal->handle($action, $user, 'other', 'too late'))
        ->toThrow(DomainException::class, 'Appeal window has expired');
});

test('FileAppeal rejects duplicate appeal', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);
    ModerationAppeal::factory()->create(['action_id' => $action->id, 'appellant_id' => $user->id]);

    $fileAppeal = resolve(FileAppeal::class);

    expect(fn () => $fileAppeal->handle($action, $user, 'other', 'again'))
        ->toThrow(DomainException::class, 'Appeal already exists');
});

test('ReviewAppeal upholds decision', function (): void {
    $reviewer = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => User::factory()->create()->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    $reviewAppeal = resolve(ReviewAppeal::class);
    $reviewAppeal->handle($appeal, $reviewer, AppealStatus::Upheld, 'Decision was correct');

    $appeal->refresh();
    expect($appeal->status)->toBe(AppealStatus::Upheld)
        ->and($appeal->reviewer_id)->toBe($reviewer->id)
        ->and($appeal->reviewer_notes)->toBe('Decision was correct')
        ->and($appeal->resolved_at)->not->toBeNull();
});

test('ReviewAppeal rejects same moderator as reviewer', function (): void {
    $moderator = User::factory()->create();
    $action = ModerationAction::factory()->create(['moderator_id' => $moderator->id]);
    $appeal = ModerationAppeal::factory()->create(['action_id' => $action->id]);

    $reviewAppeal = resolve(ReviewAppeal::class);

    expect(fn () => $reviewAppeal->handle($appeal, $moderator, AppealStatus::Upheld, 'notes'))
        ->toThrow(DomainException::class, 'Reviewer must be different');
});
