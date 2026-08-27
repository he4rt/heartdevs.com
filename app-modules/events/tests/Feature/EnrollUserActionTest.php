<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\DTOs\EnrollUserDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Events\EnrollmentWaitlisted;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;
use Symfony\Component\HttpFoundation\Response;

function createRsvpEvent(array $eventAttributes = [], array $policyAttributes = []): Event
{
    return Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->rsvp()->state($policyAttributes), 'enrollmentPolicy')
        ->create($eventAttributes);
}

test('when a user enrolls in an rsvp event, then enrollment is confirmed with audit trail', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $user = User::factory()->create();
    $event = createRsvpEvent([], ['xp_on_confirmed' => 50]);

    $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect($enrollment->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($enrollment->enrolled_at)->not->toBeNull()
        ->and($enrollment->confirmed_at)->not->toBeNull();

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->from_status)->toBeNull()
        ->and($transition->to_status)->toBe(EnrollmentStatus::Confirmed)
        ->and($transition->actor_id)->toBe($user->id)
        ->and($transition->triggered_by)->toBe(TriggeredBy::User);

    EventFacade::assertDispatched(fn (EnrollmentConfirmed $event): bool => $event->enrollmentId === $enrollment->id
        && $event->eventId === $enrollment->event_id
        && $event->userId === $user->id
        && $event->xpRewardOnConfirmed === 50);
});

test('when concurrent enrollment hits unique index, then already enrolled exception is thrown', function (): void {
    $user = User::factory()->create();
    $event = createRsvpEvent();
    $dto = EnrollUserDTO::fromModels($event, $user);

    $raced = false;

    Enrollment::creating(static function (Enrollment $enrollment) use (&$raced): void {
        if ($raced) {
            return;
        }

        $raced = true;

        Enrollment::withoutEvents(static function () use ($enrollment): void {
            Enrollment::factory()->create([
                'event_id' => $enrollment->event_id,
                'user_id' => $enrollment->user_id,
                'status' => EnrollmentStatus::Confirmed,
                'enrolled_at' => now(),
                'confirmed_at' => now(),
            ]);
        });
    });

    try {
        expect(fn (): Enrollment => resolve(EnrollUserAction::class)->handle($dto))
            ->toThrow(EnrollmentException::class);
    } finally {
        Enrollment::flushEventListeners();
    }
});

test('when a user enrolls twice in the same event, then duplicate enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $event = createRsvpEvent();

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
})->throws(EnrollmentException::class);

test('when a user enrolls in a past event, then enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->past()
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create();

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
})->throws(EnrollmentException::class);

test('when a user enrolls in a draft event, then enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create(['status' => EventStatus::Draft]);

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
})->throws(EnrollmentException::class);

test('when an event uses application enrollment method, then rsvp enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->state([
            'enrollment_method' => EnrollmentMethod::Application,
        ]), 'enrollmentPolicy')
        ->create();

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
})->throws(EnrollmentException::class);

test('when event is at capacity with waitlist enabled, then enrollment is waitlisted', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class, EnrollmentWaitlisted::class]);

    $user = User::factory()->create();
    $event = createRsvpEvent([], [
        'capacity' => 1,
        'has_waitlist' => true,
    ]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect($enrollment->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($enrollment->waitlist_position)->toBe(1)
        ->and($enrollment->confirmed_at)->toBeNull();

    EventFacade::assertNotDispatched(EnrollmentConfirmed::class);

    EventFacade::assertDispatched(fn (EnrollmentWaitlisted $event): bool => $event->enrollmentId === $enrollment->id
        && $event->eventId === $enrollment->event_id
        && $event->userId === $user->id
        && $event->waitlistPosition === 1);
});

test('when event is at capacity without waitlist, then enrollment is rejected with 422', function (): void {
    $user = User::factory()->create();
    $event = createRsvpEvent([], [
        'capacity' => 1,
        'has_waitlist' => false,
    ]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    try {
        resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
        expect(value: false)->toBeTrue('Expected EnrollmentException was not thrown');
    } catch (EnrollmentException $enrollmentException) {
        expect($enrollmentException->getCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
});

test('when event has unlimited capacity, then all enrollments are confirmed', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class, EnrollmentWaitlisted::class]);

    $event = createRsvpEvent([], [
        'capacity' => null,
        'has_waitlist' => true,
    ]);

    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

        expect($enrollment->status)->toBe(EnrollmentStatus::Confirmed);
    }

    expect(Enrollment::query()->where('event_id', $event->id)->active()->count())->toBe(3);

    EventFacade::assertNotDispatched(EnrollmentWaitlisted::class);
});

test('when multiple users enroll beyond capacity with waitlist, then fifo waitlist positions are assigned', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class, EnrollmentWaitlisted::class]);

    $event = createRsvpEvent([], [
        'capacity' => 2,
        'has_waitlist' => true,
    ]);

    $users = User::factory()->count(4)->create();
    $results = [];

    foreach ($users as $user) {
        $results[] = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
    }

    expect(Enrollment::query()->where('event_id', $event->id)->active()->count())->toBe(2)
        ->and($results[0]->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($results[1]->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($results[2]->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($results[2]->waitlist_position)->toBe(1)
        ->and($results[3]->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($results[3]->waitlist_position)->toBe(2);
});

test('when enrollments are processed in rapid succession, then active count never exceeds capacity', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class, EnrollmentWaitlisted::class]);

    $event = createRsvpEvent([], [
        'capacity' => 2,
        'has_waitlist' => true,
    ]);

    $users = User::factory()->count(5)->create();

    foreach ($users as $user) {
        resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

        expect(Enrollment::query()->where('event_id', $event->id)->active()->count())->toBeLessThanOrEqual(2);
    }

    expect(Enrollment::query()->where('event_id', $event->id)->active()->count())->toBe(2)
        ->and(Enrollment::query()->where('event_id', $event->id)->waitlisted()->count())->toBe(3);
});

test('when checked-in enrollment occupies the last seat, then new enrollment is waitlisted', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $user = User::factory()->create();
    $event = createRsvpEvent([], [
        'capacity' => 1,
        'has_waitlist' => true,
    ]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::CheckedIn,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
        'checked_in_at' => now(),
    ]);

    $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect($enrollment->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($enrollment->waitlist_position)->toBe(1);

    EventFacade::assertNotDispatched(EnrollmentConfirmed::class);
});

test('when event has available capacity, then enrollment is confirmed', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $user = User::factory()->create();
    $event = createRsvpEvent([], [
        'capacity' => 2,
        'has_waitlist' => true,
    ]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    $enrollment = resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect($enrollment->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($enrollment->confirmed_at)->not->toBeNull();

    EventFacade::assertDispatched(EnrollmentConfirmed::class);
});

test('when duplicate enrollment exists in database, then only one enrollment record is kept', function (): void {
    $user = User::factory()->create();
    $event = createRsvpEvent();

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));

    expect(Enrollment::query()->where('event_id', $event->id)->where('user_id', $user->id)->count())->toBe(1);
});

// ── Application enrollment ────────────────────────────────────────────────────

test('when a user submits an application, then enrollment is pending with application_data and audit trail', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->application([
            ['key' => 'why_join', 'type' => 'text', 'label' => 'Why do you want to join?', 'required' => true],
        ]), 'enrollmentPolicy')
        ->create();

    $dto = new EnrollUserDTO(
        eventId: $event->id,
        userId: $user->id,
        applicationData: ['why_join' => 'I love PHP!'],
    );

    $enrollment = resolve(EnrollUserAction::class)->handle($dto);

    expect($enrollment->status)->toBe(EnrollmentStatus::Pending)
        ->and($enrollment->confirmed_at)->toBeNull()
        ->and($enrollment->application_data)->toBe(['why_join' => 'I love PHP!']);

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->from_status)->toBeNull()
        ->and($transition->to_status)->toBe(EnrollmentStatus::Pending)
        ->and($transition->triggered_by)->toBe(TriggeredBy::User);

    EventFacade::assertNotDispatched(EnrollmentConfirmed::class);
});

test('when application is submitted without applicationData, then exception is thrown', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    resolve(EnrollUserAction::class)->handle(EnrollUserDTO::fromModels($event, $user));
})->throws(EnrollmentException::class);

test('when application is missing a required field, then exception is thrown', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->application([
            ['key' => 'why', 'type' => 'text', 'label' => 'Why?', 'required' => true],
        ]), 'enrollmentPolicy')
        ->create();

    $dto = new EnrollUserDTO(
        eventId: $event->id,
        userId: $user->id,
        applicationData: ['why' => ''],
    );

    resolve(EnrollUserAction::class)->handle($dto);
})->throws(EnrollmentException::class);

test('when application is submitted with no schema defined, then enrollment is pending', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    $dto = new EnrollUserDTO(
        eventId: $event->id,
        userId: $user->id,
        applicationData: [],
    );

    $enrollment = resolve(EnrollUserAction::class)->handle($dto);

    expect($enrollment->status)->toBe(EnrollmentStatus::Pending);
});

test('when application event is past, then application enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()
        ->published()
        ->past()
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    $dto = new EnrollUserDTO(
        eventId: $event->id,
        userId: $user->id,
        applicationData: [],
    );

    resolve(EnrollUserAction::class)->handle($dto);
})->throws(EnrollmentException::class);
