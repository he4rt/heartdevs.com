<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Actions\RejectApplicationAction;
use He4rt\Events\Enrollment\DTOs\RejectApplicationDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

test('when an application is rejected with a reason, then enrollment transitions to rejected', function (): void {
    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now(),
        'application_data' => ['q1' => 'My answer'],
    ]);

    $result = resolve(RejectApplicationAction::class)->handle(
        new RejectApplicationDTO(
            enrollmentId: $enrollment->id,
            actorId: $organizer->id,
            reason: 'Not enough experience.',
        ),
    );

    expect($result->status)->toBe(EnrollmentStatus::Rejected)
        ->and($result->rejection_reason)->toBe('Not enough experience.');

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->latest()
        ->first();

    expect($transition->from_status)->toBe(EnrollmentStatus::Pending)
        ->and($transition->to_status)->toBe(EnrollmentStatus::Rejected)
        ->and($transition->triggered_by)->toBe(TriggeredBy::Admin)
        ->and($transition->actor_id)->toBe($organizer->id)
        ->and($transition->reason)->toBe('Not enough experience.');
});

test('when rejecting a non-pending enrollment, then exception is thrown', function (): void {
    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    resolve(RejectApplicationAction::class)->handle(
        new RejectApplicationDTO(
            enrollmentId: $enrollment->id,
            actorId: $organizer->id,
            reason: 'Test reason.',
        ),
    );
})->throws(EnrollmentException::class);

test('when application is rejected, then application_data is preserved', function (): void {
    $organizer = User::factory()->create();
    $tenant = Tenant::factory()->create();
    $event = Event::factory()
        ->published()
        ->upcoming()
        ->for($tenant)
        ->has(EnrollmentPolicy::factory()->application(), 'enrollmentPolicy')
        ->create();

    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now(),
        'application_data' => ['q1' => 'Original answer'],
    ]);

    $result = resolve(RejectApplicationAction::class)->handle(
        new RejectApplicationDTO(
            enrollmentId: $enrollment->id,
            actorId: $organizer->id,
            reason: 'Rejected.',
        ),
    );

    expect($result->application_data)->toBe(['q1' => 'Original answer']);
});
