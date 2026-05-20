<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Actions\EnrollUserAction;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Events\EnrollmentConfirmed;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;

uses(RefreshDatabase::class);

function createRsvpEvent(Tenant $tenant, array $eventAttributes = [], array $policyAttributes = []): Event
{
    return Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->rsvp()->state($policyAttributes), 'enrollmentPolicy')
        ->create($eventAttributes);
}

test('when a user enrolls in an rsvp event, then enrollment is confirmed with audit trail', function (): void {
    EventFacade::fake([EnrollmentConfirmed::class]);

    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createRsvpEvent($tenant, [], ['xp_on_confirmed' => 50]);

    $enrollment = resolve(EnrollUserAction::class)->handle($event, $user);

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
        && $event->xpRewardRsvp === 50);
});

test('when a user enrolls twice in the same event, then duplicate enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createRsvpEvent($tenant);

    resolve(EnrollUserAction::class)->handle($event, $user);

    resolve(EnrollUserAction::class)->handle($event, $user);
})->throws(EnrollmentException::class);

test('when a user enrolls in a past event, then enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->published()
        ->past()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create();

    resolve(EnrollUserAction::class)->handle($event, $user);
})->throws(EnrollmentException::class);

test('when a user enrolls in a draft event, then enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create(['status' => EventStatus::Draft]);

    resolve(EnrollUserAction::class)->handle($event, $user);
})->throws(EnrollmentException::class);

test('when an event uses application enrollment method, then rsvp enrollment is rejected', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->state([
            'enrollment_method' => EnrollmentMethod::Application,
        ]), 'enrollmentPolicy')
        ->create();

    resolve(EnrollUserAction::class)->handle($event, $user);
})->throws(EnrollmentException::class);

test('when duplicate enrollment exists in database, then only one enrollment record is kept', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = createRsvpEvent($tenant);

    resolve(EnrollUserAction::class)->handle($event, $user);

    expect(Enrollment::query()->where('event_id', $event->id)->where('user_id', $user->id)->count())->toBe(1);
});
