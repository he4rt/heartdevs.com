<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Timeline>
 */
class TimelineFactory extends Factory
{
    protected $model = Timeline::class;

    public function definition(): array
    {
        return [
            'postable_type' => fake()->word(),
            'postable_id' => fake()->randomNumber(),
            'is_reported' => fake()->boolean(),
            'is_ignored' => fake()->boolean(),
            'pinned' => fake()->boolean(),
            'views' => fake()->randomNumber(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'user_id' => User::factory(),
        ];
    }
}
