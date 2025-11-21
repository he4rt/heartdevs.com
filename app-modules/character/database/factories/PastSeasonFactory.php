<?php

declare(strict_types=1);

namespace He4rt\Character\Database\Factories;

use He4rt\Character\Models\Character;
use He4rt\Character\Models\PastSeason;
use He4rt\Season\Models\Season;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastSeason>
 */
final class PastSeasonFactory extends Factory
{
    protected $model = PastSeason::class;

    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'tenant_id' => Tenant::factory(),
            'character_id' => Character::factory(),
            'ranking_position' => fake()->numberBetween(1, 1000),
            'experience' => fake()->numberBetween(1, 1000),
            'level' => 1,
            'messages_count' => fake()->numberBetween(1, 1000),
            'badges_count' => fake()->numberBetween(1, 1000),
            'meetings_count' => fake()->numberBetween(1, 1000),
        ];
    }
}
