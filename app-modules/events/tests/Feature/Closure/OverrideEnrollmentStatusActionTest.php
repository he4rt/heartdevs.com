<?php

declare(strict_types=1);

use He4rt\Events\Closure\Actions\OverrideEnrollmentStatusAction;
use He4rt\Events\Closure\DTOs\OverrideEnrollmentStatusDTO;
use He4rt\Events\Closure\Exceptions\OverrideEnrollmentStatusException;
use He4rt\Events\Enrollment\Actions\TransitionEnrollmentAction;
use He4rt\Events\Enrollment\DTOs\TransitionEnrollmentDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

function createPastEvent(): Event
{
    $tenant = Tenant::factory()->create();
    $startsAt = now()->subDays(2)->setTime(9, 0);

    return Event::factory()
        ->for($tenant)
        ->create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ]);
}

test('when admin overrides no_show enrollment to attended with reason, then transition is recorded with admin trigger', function (): void {
    $event = createPastEvent();
    $organizer = User::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::NoShow,
        'confirmed_at' => now()->subDay(),
    ]);

    $transition = resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::NoShow,
            toStatus: EnrollmentStatus::Attended,
            actorId: $organizer->id,
            reason: 'Participant was actually present',
        ),
    );

    expect($transition->from_status)->toBe(EnrollmentStatus::NoShow)
        ->and($transition->to_status)->toBe(EnrollmentStatus::Attended)
        ->and($transition->triggered_by)->toBe(TriggeredBy::Admin)
        ->and($transition->actor_id)->toBe($organizer->id)
        ->and($transition->reason)->toBe('Participant was actually present')
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::Attended)
        ->and($enrollment->fresh()->attended_at)->not->toBeNull();
});

test('when admin overrides confirmed enrollment to checked_in with reason, then status transitions and timestamp is set', function (): void {
    $event = createPastEvent();
    $organizer = User::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::Confirmed,
            toStatus: EnrollmentStatus::CheckedIn,
            actorId: $organizer->id,
            reason: 'Late arrival',
        ),
    );

    $enrollment->refresh();

    expect($enrollment->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($enrollment->checked_in_at)->not->toBeNull();

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('to_status', EnrollmentStatus::CheckedIn)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->triggered_by)->toBe(TriggeredBy::Admin)
        ->and($transition->reason)->toBe('Late arrival');
});

test('when admin tries to override without reason, then reason required exception is thrown', function (): void {
    $event = createPastEvent();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::NoShow,
    ]);

    expect(fn (): EnrollmentTransition => resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::NoShow,
            toStatus: EnrollmentStatus::Attended,
            actorId: User::factory()->create()->id,
            reason: '   ',
        ),
    ))->toThrow(OverrideEnrollmentStatusException::class);
});

test('when admin tries to override from cancelled to attended, then override not allowed exception is thrown', function (): void {
    $event = createPastEvent();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Cancelled,
    ]);

    expect(fn (): EnrollmentTransition => resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::Cancelled,
            toStatus: EnrollmentStatus::Attended,
            actorId: User::factory()->create()->id,
            reason: 'Should not work',
        ),
    ))->toThrow(OverrideEnrollmentStatusException::class);
});

test('when allowedTargetsFor is queried for NoShow, then it returns only Attended', function (): void {
    $targets = OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::NoShow);

    expect($targets)->toBe([EnrollmentStatus::Attended]);
});

test('when allowedTargetsFor is queried for Confirmed, then it returns only CheckedIn', function (): void {
    $targets = OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::Confirmed);

    expect($targets)->toBe([EnrollmentStatus::CheckedIn]);
});

test('when allowedTargetsFor is queried for a terminal or unrelated status, then it returns an empty array', function (): void {
    expect(OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::Attended))->toBeEmpty()
        ->and(OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::Cancelled))->toBeEmpty()
        ->and(OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::Rejected))->toBeEmpty()
        ->and(OverrideEnrollmentStatusAction::allowedTargetsFor(EnrollmentStatus::NoShow))->not->toBeEmpty();
});

test('when admin tries to override from no_show to confirmed, then override not allowed exception is thrown', function (): void {
    $event = createPastEvent();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::NoShow,
    ]);

    expect(fn (): EnrollmentTransition => resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::NoShow,
            toStatus: EnrollmentStatus::Confirmed,
            actorId: User::factory()->create()->id,
            reason: 'Should not work',
        ),
    ))->toThrow(OverrideEnrollmentStatusException::class);
});

test('when admin overrides a stale confirmed enrollment that is already no_show, then current status is enforced', function (): void {
    $event = createPastEvent();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    resolve(TransitionEnrollmentAction::class)->handle(
        new TransitionEnrollmentDTO(
            enrollment: $enrollment->fresh(),
            toStatus: EnrollmentStatus::NoShow,
            triggeredBy: TriggeredBy::System,
        ),
    );

    expect(fn (): EnrollmentTransition => resolve(OverrideEnrollmentStatusAction::class)->handle(
        new OverrideEnrollmentStatusDTO(
            enrollment: $enrollment,
            fromStatus: EnrollmentStatus::Confirmed,
            toStatus: EnrollmentStatus::CheckedIn,
            actorId: User::factory()->create()->id,
            reason: 'Late arrival',
        ),
    ))->toThrow(function (OverrideEnrollmentStatusException $exception): void {
        expect($exception->getCode())->toBe(409);
    });

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::NoShow);
});
