<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnrollmentPolicy> */
final class EnrollmentPolicyFactory extends Factory
{
    protected $model = EnrollmentPolicy::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'enrollment_method' => fake()->randomElement(EnrollmentMethod::cases()),
            'check_in_method' => fake()->randomElement(CheckInMethod::cases()),
            'capacity' => fake()->optional()->numberBetween(10, 200),
            'has_waitlist' => false,
            'attendance_requirement' => AttendanceRequirement::AllDays,
            'minimum_days' => null,
            'cancellation_deadline_hours' => null,
            'xp_on_confirmed' => 0,
            'xp_on_checked_in' => 0,
            'xp_on_attended' => 0,
            'application_schema' => null,
        ];
    }
}
