<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
final class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => EnrollmentStatus::Pending,
            'is_public' => true,
            'waitlist_position' => null,
            'application_data' => null,
            'rejection_reason' => null,
            'enrolled_at' => null,
            'confirmed_at' => null,
            'checked_in_at' => null,
            'attended_at' => null,
            'cancelled_at' => null,
        ];
    }
}
