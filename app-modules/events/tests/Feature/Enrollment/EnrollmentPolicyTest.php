<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;

function createEventWithPolicy(array $eventAttributes = [], array $policyAttributes = []): Event
{
    $startsAt = now()->setTime(9, 0);

    return Event::factory()
        ->has(EnrollmentPolicy::factory()->state(array_merge([
            'attendance_requirement' => AttendanceRequirement::AllDays,
            'minimum_days' => null,
        ], $policyAttributes)), 'enrollmentPolicy')
        ->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->setTime(18, 0),
            'status' => EventStatus::Published,
        ], $eventAttributes));
}

test('when checked_in enrollment meets all_days requirement, then resolveAttendance returns Attended', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = createEventWithPolicy([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::AllDays,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);
    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->clone()->addDay()->toDateString(), 'method' => CheckInMethod::Manual]);
    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->clone()->addDays(2)->toDateString(), 'method' => CheckInMethod::Manual]);

    expect($event->enrollmentPolicy->resolveAttendance($enrollment))->toBe(EnrollmentStatus::Attended);
});

test('when checked_in enrollment is missing days for all_days requirement, then resolveAttendance returns NoShow', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = createEventWithPolicy([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::AllDays,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);

    expect($event->enrollmentPolicy->resolveAttendance($enrollment))->toBe(EnrollmentStatus::NoShow);
});

test('when checked_in enrollment has at least one check-in and requirement is any_day, then resolveAttendance returns Attended', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = createEventWithPolicy([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::AnyDay,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);

    expect($event->enrollmentPolicy->resolveAttendance($enrollment))->toBe(EnrollmentStatus::Attended);
});

test('when checked_in enrollment meets minimum_days requirement, then resolveAttendance returns Attended', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = createEventWithPolicy([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::MinimumDays,
        'minimum_days' => 2,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);
    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->clone()->addDay()->toDateString(), 'method' => CheckInMethod::Manual]);

    expect($event->enrollmentPolicy->resolveAttendance($enrollment))->toBe(EnrollmentStatus::Attended);
});

test('when checked_in enrollment is below minimum_days requirement, then resolveAttendance returns NoShow', function (): void {
    $startsAt = now()->setTime(9, 0);
    $event = createEventWithPolicy([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::MinimumDays,
        'minimum_days' => 2,
    ]);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $enrollment->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);

    expect($event->enrollmentPolicy->resolveAttendance($enrollment))->toBe(EnrollmentStatus::NoShow);
});
