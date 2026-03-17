<?php

declare(strict_types=1);

namespace He4rt\Community\Database\Factories;

use He4rt\Community\Meeting\Models\MeetingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingType>
 */
final class MeetingTypeFactory extends Factory
{
    protected $model = MeetingType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'week_day' => fake()->numberBetween(0, 6),
            'start_at' => fake()->time('H:i'),
        ];
    }
}
