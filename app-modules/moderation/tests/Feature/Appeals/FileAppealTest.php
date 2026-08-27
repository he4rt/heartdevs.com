<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Appeals\FileAppeal;
use He4rt\Moderation\Appeals\ModerationAppeal;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\AppealStatus;

test('creates appeal with correct SLA deadline', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);

    $appeal = resolve(FileAppeal::class)->handle($action, $user, 'context_misunderstood', 'I was joking');

    expect($appeal)->toBeInstanceOf(ModerationAppeal::class)
        ->and($appeal->status)->toBe(AppealStatus::Pending)
        ->and($appeal->appellant_id)->toBe($user->id)
        ->and($appeal->reason_category)->toBe('context_misunderstood')
        ->and($appeal->reason_text)->toBe('I was joking')
        ->and((int) now()->diffInHours($appeal->sla_deadline))->toBeBetween(47, 48);
});

test('rejects appeal when window has expired', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'created_at' => now()->subDays(8),
    ]);

    expect(fn () => resolve(FileAppeal::class)->handle($action, $user, 'other', 'too late'))
        ->toThrow(DomainException::class, 'Appeal window has expired');
});

test('rejects appeal on last day of window boundary', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'created_at' => now()->subDays(7)->subHour(),
    ]);

    expect(fn () => resolve(FileAppeal::class)->handle($action, $user, 'other', 'just over'))
        ->toThrow(DomainException::class, 'Appeal window has expired');
});

test('allows appeal on exactly day 7', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'created_at' => now()->subDays(7)->addMinute(),
    ]);

    $appeal = resolve(FileAppeal::class)->handle($action, $user, 'wrong_person', 'not me');

    expect($appeal)->toBeInstanceOf(ModerationAppeal::class);
});

test('rejects duplicate appeal on same action', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);

    resolve(FileAppeal::class)->handle($action, $user, 'context_misunderstood', 'first try');

    expect(fn () => resolve(FileAppeal::class)->handle($action, $user, 'other', 'second try'))
        ->toThrow(DomainException::class, 'Appeal already exists');
});

test('allows appeal from different user than action target', function (): void {
    $actionTarget = User::factory()->create();
    $appellant = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $actionTarget->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);

    $appeal = resolve(FileAppeal::class)->handle($action, $appellant, 'wrong_person', 'wasnt me');

    expect($appeal->appellant_id)->toBe($appellant->id);
});

test('appeal has null reason_text when not provided', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create(['case_id' => $case->id, 'created_at' => now()]);

    $appeal = resolve(FileAppeal::class)->handle($action, $user, 'disproportionate', reasonText: null);

    expect($appeal->reason_text)->toBeNull()
        ->and($appeal->reason_category)->toBe('disproportionate');
});
