<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Actions\ApproveApplicationAction;
use He4rt\Events\Enrollment\DTOs\ApproveApplicationDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Events\EnrollmentWaitlisted;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

function createPendingApplicationEnrollment(Event $event, ?User $user = null): Enrollment
{
    $user ??= User::factory()->create();

    return Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now(),
        'application_data' => [0 => 'My answer'],
    ]);
}

function createApplicationEvent(Tenant $tenant, array $policyAttributes = []): Event
{
    return Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->application()->state($policyAttributes), 'enrollmentPolicy')
        ->create();
}

test('when an application is approved, then enrollment transitions to confirmed with audit trail', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createApplicationEvent($tenant, ['xp_on_confirmed' => 100]);
    $enrollment = createPendingApplicationEnrollment($event);

    $result = resolve(ApproveApplicationAction::class)->handle(
        new ApproveApplicationDTO(enrollmentId: $enrollment->id, actorId: $organizer->id),
    );

    expect($result->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($result->confirmed_at)->not->toBeNull();

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->latest()
        ->first();

    expect($transition->from_status)->toBe(EnrollmentStatus::Pending)
        ->and($transition->to_status)->toBe(EnrollmentStatus::Confirmed)
        ->and($transition->triggered_by)->toBe(TriggeredBy::Admin)
        ->and($transition->actor_id)->toBe($organizer->id);

    EventFacade::assertDispatched(fn (EnrollmentConfirmed $e): bool => $e->enrollmentId === $enrollment->id
        && $e->xpRewardOnConfirmed === 100);
});

test('when event is at capacity and has waitlist, then approval results in waitlisted', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class, EnrollmentWaitlisted::class]);

    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createApplicationEvent($tenant, ['capacity' => 1, 'has_waitlist' => true]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    $enrollment = createPendingApplicationEnrollment($event);

    $result = resolve(ApproveApplicationAction::class)->handle(
        new ApproveApplicationDTO(enrollmentId: $enrollment->id, actorId: $organizer->id),
    );

    expect($result->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($result->waitlist_position)->toBe(1);

    EventFacade::assertNotDispatched(EnrollmentConfirmed::class);
    EventFacade::assertDispatched(fn (EnrollmentWaitlisted $e): bool => $e->enrollmentId === $enrollment->id
        && $e->waitlistPosition === 1);
});

test('when event is at capacity without waitlist, then approval throws event full exception', function (): void {
    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createApplicationEvent($tenant, ['capacity' => 1, 'has_waitlist' => false]);

    $existingUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => $existingUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    $enrollment = createPendingApplicationEnrollment($event);

    resolve(ApproveApplicationAction::class)->handle(
        new ApproveApplicationDTO(enrollmentId: $enrollment->id, actorId: $organizer->id),
    );
})->throws(EnrollmentException::class);

test('when approving a non-pending enrollment, then exception is thrown', function (): void {
    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createApplicationEvent($tenant);

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    resolve(ApproveApplicationAction::class)->handle(
        new ApproveApplicationDTO(enrollmentId: $enrollment->id, actorId: $organizer->id),
    );
})->throws(EnrollmentException::class);

test('when event has no capacity limit, then approval always confirms', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createApplicationEvent($tenant, ['capacity' => null]);

    $enrollments = collect(range(1, 3))->map(
        fn (): Enrollment => createPendingApplicationEnrollment($event),
    );

    foreach ($enrollments as $enrollment) {
        $result = resolve(ApproveApplicationAction::class)->handle(
            new ApproveApplicationDTO(enrollmentId: $enrollment->id, actorId: $organizer->id),
        );
        expect($result->status)->toBe(EnrollmentStatus::Confirmed);
    }

    EventFacade::assertDispatchedTimes(EnrollmentConfirmed::class, 3);
});
