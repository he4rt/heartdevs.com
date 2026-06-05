<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Closure\Actions\CloseEventAction;
use He4rt\Events\Closure\Events\ParticipantAttended;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

function createEventForClosure(array $eventAttributes = [], array $policyAttributes = []): Event
{
    $tenant = Tenant::factory()->create();
    $startsAt = now()->subDays(3)->setTime(9, 0);

    return Event::factory()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->state(array_merge([
            'attendance_requirement' => AttendanceRequirement::AllDays,
            'minimum_days' => null,
            'xp_on_attended' => 0,
        ], $policyAttributes)), 'enrollmentPolicy')
        ->create(array_merge([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
            'status' => EventStatus::Published,
        ], $eventAttributes));
}

test('when checked_in enrollment meets all_days, then action transitions to Attended and dispatches ParticipantAttended', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $startsAt = now()->subDays(3)->setTime(9, 0);
    $event = createEventForClosure([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::AllDays,
        'xp_on_attended' => 75,
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

    $attended = resolve(CloseEventAction::class)->handle($event);

    expect($attended)->toBe(1)
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::Attended)
        ->and($enrollment->fresh()->attended_at)->not->toBeNull();

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('to_status', EnrollmentStatus::Attended)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->from_status)->toBe(EnrollmentStatus::CheckedIn)
        ->and($transition->triggered_by)->toBe(TriggeredBy::System);

    EventFacade::assertDispatched(fn (ParticipantAttended $e): bool => $e->enrollmentId === $enrollment->id
        && $e->eventId === $event->id
        && $e->xpRewardOnAttended === 75);
});

test('when checked_in enrollment is missing check-ins for all_days, then action transitions to NoShow without dispatching event', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $startsAt = now()->subDays(3)->setTime(9, 0);
    $event = createEventForClosure([
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

    $attended = resolve(CloseEventAction::class)->handle($event);

    expect($attended)->toBe(0)
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::NoShow);

    EventFacade::assertNotDispatched(ParticipantAttended::class);
});

test('when confirmed enrollment never checked in, then action transitions to NoShow', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $event = createEventForClosure();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    $attended = resolve(CloseEventAction::class)->handle($event);

    expect($attended)->toBe(0)
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::NoShow);

    EventFacade::assertNotDispatched(ParticipantAttended::class);
});

test('when terminal enrollment is present, then action skips it', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $event = createEventForClosure();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Cancelled,
        'cancelled_at' => now()->subDay(),
    ]);

    $attended = resolve(CloseEventAction::class)->handle($event);

    expect($attended)->toBe(0)
        ->and($enrollment->fresh()->status)->toBe(EnrollmentStatus::Cancelled);

    $transitionCount = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->count();

    expect($transitionCount)->toBe(0);
});

test('when action runs twice, then second run is no-op (idempotent)', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $event = createEventForClosure();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    $first = resolve(CloseEventAction::class)->handle($event);
    $second = resolve(CloseEventAction::class)->handle($event);

    expect($first)->toBe(0)
        ->and($second)->toBe(0);

    $transitions = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('to_status', EnrollmentStatus::NoShow)
        ->count();

    expect($transitions)->toBe(1);
});

test('when action processes multiple enrollments, then each is closed independently with system audit trail', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $startsAt = now()->subDays(3)->setTime(9, 0);
    $event = createEventForClosure([
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->clone()->addDays(2)->setTime(18, 0),
    ], [
        'attendance_requirement' => AttendanceRequirement::AnyDay,
    ]);
    $confirmed = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);
    $checkedIn = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now()->subDay(),
    ]);

    CheckIn::factory()->create(['enrollment_id' => $checkedIn->id, 'event_date' => $startsAt->toDateString(), 'method' => CheckInMethod::Manual]);

    resolve(CloseEventAction::class)->handle($event);

    expect($confirmed->fresh()->status)->toBe(EnrollmentStatus::NoShow)
        ->and($checkedIn->fresh()->status)->toBe(EnrollmentStatus::Attended)
        ->and($checkedIn->fresh()->attended_at)->not->toBeNull();

    foreach ([$confirmed->id, $checkedIn->id] as $enrollmentId) {
        $transition = EnrollmentTransition::query()
            ->where('enrollment_id', $enrollmentId)
            ->first();

        expect($transition)->not->toBeNull()
            ->and($transition->triggered_by)->toBe(TriggeredBy::System);
    }
});
