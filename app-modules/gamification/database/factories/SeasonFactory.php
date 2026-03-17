<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
final class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'description' => fake()->text(),
            'messages_count' => fake()->randomNumber(2),
            'participants_count' => fake()->randomNumber(2),
            'meeting_count' => fake()->randomNumber(2),
            'badges_count' => fake()->randomNumber(2),
            'started_at' => fake()->dateTimeBetween('-1 hour'),
            'ended_at' => fake()->dateTimeBetween('+1 hour', '+2 hour'),
        ];
    }
}
