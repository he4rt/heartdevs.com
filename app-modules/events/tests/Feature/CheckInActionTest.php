<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Actions\CheckInAction;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Events\ParticipantCheckedIn;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

function createConfirmedEnrollmentForCheckIn(array $eventAttributes = [], array $enrollmentAttributes = []): Enrollment
{
    $tenant = Tenant::factory()->create();
    $participant = User::factory()->create();
    $startsAt = now()->setTime(9, 0);

    $event = Event::factory()
        ->for($tenant)
        ->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ], $eventAttributes));

    return Enrollment::factory()->create(array_merge([
        'event_id' => $event->id,
        'user_id' => $participant->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ], $enrollmentAttributes));
}

test('when organizer checks in confirmed enrollment, then check-in is recorded and enrollment transitions', function (): void {
    EventFacade::fake([ParticipantCheckedIn::class]);

    $organizer = User::factory()->create();
    $enrollment = createConfirmedEnrollmentForCheckIn();

    $checkIn = resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: ['actor_user_id' => $organizer->id],
        eventDate: now(),
    );

    expect($checkIn)->toBeInstanceOf(CheckIn::class)
        ->and($checkIn->enrollment_id)->toBe($enrollment->id)
        ->and($checkIn->method)->toBe(CheckInMethod::Manual)
        ->and($checkIn->event_date->isSameDay(now()))->toBeTrue()
        ->and($checkIn->checked_in_at)->not->toBeNull()
        ->and($checkIn->payload)->toBe(['actor_user_id' => $organizer->id])
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($enrollment->fresh()->checked_in_at)->not->toBeNull();

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('to_status', EnrollmentStatus::CheckedIn)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->from_status)->toBe(EnrollmentStatus::Confirmed)
        ->and($transition->actor_id)->toBe($organizer->id)
        ->and($transition->triggered_by)->toBe(TriggeredBy::Admin);

    EventFacade::assertDispatched(fn (ParticipantCheckedIn $event): bool => $event->enrollmentId === $enrollment->id
        && $event->eventId === $enrollment->event_id
        && $event->userId === $enrollment->user_id
        && $event->checkInId === $checkIn->id);
});

test('when checked-in enrollment checks in on another event date, then only check-in record is created', function (): void {
    $organizer = User::factory()->create();
    $startsAt = now()->setTime(9, 0);
    $enrollment = createConfirmedEnrollmentForCheckIn([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDay()->setTime(18, 0),
    ], [
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create([
        'enrollment_id' => $enrollment->id,
        'event_date' => $startsAt->toDateString(),
        'method' => CheckInMethod::Manual,
    ]);

    $checkIn = resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: ['actor_user_id' => $organizer->id],
        eventDate: $startsAt->clone()->addDay(),
    );

    expect($checkIn->event_date->isSameDay($startsAt->clone()->addDay()))->toBeTrue()
        ->and(CheckIn::query()->where('enrollment_id', $enrollment->id)->count())->toBe(2)
        ->and(EnrollmentTransition::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('to_status', EnrollmentStatus::CheckedIn)
            ->count())->toBe(0);
});

test('when enrollment already has check-in for event date, then duplicate is rejected', function (): void {
    $enrollment = createConfirmedEnrollmentForCheckIn();

    CheckIn::factory()->create([
        'enrollment_id' => $enrollment->id,
        'event_date' => now()->toDateString(),
        'method' => CheckInMethod::Manual,
    ]);

    expect(fn (): CheckIn => resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: ['actor_user_id' => User::factory()->create()->id],
        eventDate: now(),
    ))->toThrow(EnrollmentException::class);
});

test('when check-in date is outside event date range, then check-in is rejected', function (): void {
    $startsAt = now()->setTime(9, 0);
    $enrollment = createConfirmedEnrollmentForCheckIn([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->setTime(18, 0),
    ]);

    expect(fn (): CheckIn => resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: ['actor_user_id' => User::factory()->create()->id],
        eventDate: $startsAt->clone()->addDay(),
    ))->toThrow(EnrollmentException::class);
});

test('when enrollment status is not confirmed or checked in, then check-in is rejected', function (): void {
    $enrollment = createConfirmedEnrollmentForCheckIn(enrollmentAttributes: [
        'status' => EnrollmentStatus::Waitlisted,
        'confirmed_at' => null,
    ]);

    expect(fn (): CheckIn => resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: ['actor_user_id' => User::factory()->create()->id],
        eventDate: now(),
    ))->toThrow(EnrollmentException::class);
});

test('when manual check-in has no actor user id, then check-in is rejected', function (): void {
    $enrollment = createConfirmedEnrollmentForCheckIn();

    expect(fn (): CheckIn => resolve(CheckInAction::class)->handle(
        enrollment: $enrollment,
        method: CheckInMethod::Manual,
        payload: [],
        eventDate: now(),
    ))->toThrow(EnrollmentException::class);
});
