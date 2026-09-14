<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Enums\TriggeredBy;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnrollmentTransition> */
final class EnrollmentTransitionFactory extends Factory
{
    protected $model = EnrollmentTransition::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'from_status' => null,
            'to_status' => EnrollmentStatus::Pending,
            'actor_id' => null,
            'triggered_by' => TriggeredBy::User,
            'reason' => null,
            'metadata' => null,
        ];
    }
}
