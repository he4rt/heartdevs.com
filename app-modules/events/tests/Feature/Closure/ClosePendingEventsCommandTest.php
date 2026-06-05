<?php

declare(strict_types=1);

use He4rt\Events\Closure\Jobs\ProcessEventClosureJob;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Bus;

function makeEventForCommandTest(array $eventAttributes = [], array $policyAttributes = []): Event
{
    $tenant = Tenant::factory()->create();
    $startsAt = now()->subDays(3)->setTime(9, 0);

    return Event::factory()
        ->for($tenant)
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

test('when command runs and past event has non-terminal enrollments, then it dispatches a closure job', function (): void {
    Bus::fake([ProcessEventClosureJob::class]);

    $event = makeEventForCommandTest();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    $this->artisan('events:close-pending')
        ->expectsOutputToContain('Dispatched 1 closure job(s).')
        ->assertSuccessful();

    Bus::assertDispatched(fn (ProcessEventClosureJob $job): bool => $job->eventId === $event->id);
});

test('when command runs and event is in the future, then no job is dispatched', function (): void {
    Bus::fake([ProcessEventClosureJob::class]);

    $futureStartsAt = now()->addDays(5)->setTime(9, 0);
    $event = makeEventForCommandTest([
        'starts_at' => $futureStartsAt,
        'ends_at' => $futureStartsAt->clone()->setTime(18, 0),
    ]);
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    $this->artisan('events:close-pending');

    Bus::assertNotDispatched(ProcessEventClosureJob::class);
});

test('when command runs and past event has only terminal enrollments, then no job is dispatched', function (): void {
    Bus::fake([ProcessEventClosureJob::class]);

    $event = makeEventForCommandTest();
    Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Attended,
        'attended_at' => now()->subDay(),
    ]);

    $this->artisan('events:close-pending');

    Bus::assertNotDispatched(ProcessEventClosureJob::class);
});

test('when command runs and multiple past events need closure, then it dispatches one job per event', function (): void {
    Bus::fake([ProcessEventClosureJob::class]);

    $eventA = makeEventForCommandTest();
    $eventB = makeEventForCommandTest();
    Enrollment::factory()->create([
        'event_id' => $eventA->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
    ]);
    Enrollment::factory()->create([
        'event_id' => $eventB->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::CheckedIn,
        'checked_in_at' => now()->subDay(),
    ]);

    $this->artisan('events:close-pending');

    Bus::assertDispatchedTimes(ProcessEventClosureJob::class, 2);
});
