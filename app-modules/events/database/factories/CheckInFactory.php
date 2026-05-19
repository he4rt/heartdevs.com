<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<CheckIn> */
final class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'check_in' => Date::today(),
            'method' => fake()->randomElement(CheckInMethod::cases()),
            'payload' => null,
        ];
    }
}
