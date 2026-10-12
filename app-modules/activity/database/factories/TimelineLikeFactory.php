<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Activity\Timeline\TimelineLike;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TimelineLike> */
final class TimelineLikeFactory extends Factory
{
    protected $model = TimelineLike::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'timeline_id' => Timeline::factory(),
            'user_id' => User::factory(),
        ];
    }
}
