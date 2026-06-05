<?php

declare(strict_types=1);

use He4rt\Events\Closure\Actions\CloseEventAction;
use He4rt\Events\Closure\Events\ParticipantAttended;
use He4rt\Events\Closure\Jobs\ProcessEventClosureJob;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function makeEventForJobTest(array $eventAttributes = [], array $policyAttributes = []): Event
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

test('when job runs for an event with non-terminal enrollments, then it closes them', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    $event = makeEventForJobTest();
    $enrollment = Enrollment::factory()->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create()->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now()->subDay(),
    ]);

    new ProcessEventClosureJob($event->id)->handle(resolve(CloseEventAction::class));

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::NoShow);

    $transition = EnrollmentTransition::query()
        ->where('enrollment_id', $enrollment->id)
        ->first();

    expect($transition)->not->toBeNull()
        ->and($transition->triggered_by)->toBe(TriggeredBy::System);
});

test('when job unique id is queried, then it returns the event id', function (): void {
    $job = new ProcessEventClosureJob('event-123');

    expect($job->uniqueId())->toBe('event-123');
});

test('when job runs for a non-existent event id, then it silently returns without throwing', function (): void {
    EventFacade::fake([ParticipantAttended::class]);

    new ProcessEventClosureJob(Str::uuid()->toString())->handle(resolve(CloseEventAction::class));

    EventFacade::assertNotDispatched(ParticipantAttended::class);
});

test('when job fails, then it logs the event id and error', function (): void {
    Log::spy();

    $exception = new RuntimeException('db down');

    new ProcessEventClosureJob('event-xyz')->failed($exception);

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Event closure failed'
        && $context['event_id'] === 'event-xyz'
        && $context['error'] === 'db down');
});

test('when job is configured, then it has correct backoff, tries, and unique ttl', function (): void {
    $job = new ProcessEventClosureJob('event-1');

    expect($job->tries)->toBe(4)
        ->and($job->backoff)->toBe([1, 5, 10])
        ->and($job->uniqueFor)->toBe(1800);
});
