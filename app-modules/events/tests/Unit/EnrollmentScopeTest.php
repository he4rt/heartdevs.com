<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('when enrollments are queried with active scope, then only capacity-occupying statuses are included', function (): void {
    $event = Event::factory()->published()->upcoming()->create();

    $occupying = [
        EnrollmentStatus::Confirmed,
        EnrollmentStatus::CheckedIn,
        EnrollmentStatus::Attended,
    ];

    foreach ($occupying as $status) {
        Enrollment::factory()->create([
            'event_id' => $event->id,
            'status' => $status,
        ]);
    }

    foreach ([EnrollmentStatus::Pending, EnrollmentStatus::Waitlisted, EnrollmentStatus::Cancelled] as $status) {
        Enrollment::factory()->create([
            'event_id' => $event->id,
            'status' => $status,
        ]);
    }

    expect(Enrollment::query()->where('event_id', $event->id)->active()->count())->toBe(3);
});
