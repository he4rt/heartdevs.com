<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Database\Factories;

use He4rt\IntegrationDiscord\Models\DiscordGuild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordGuild>
 */
final class DiscordGuildFactory extends Factory
{
    protected $model = DiscordGuild::class;

    public function definition(): array
    {
        return [
            'discord_guild_id' => (string) fake()->unique()->numberBetween(100_000_000_000_000_000, 999_999_999_999_999_999),
            'name' => fake()->company(),
            'icon' => fake()->optional()->md5(),
            'description' => fake()->optional()->sentence(),
            'member_count' => fake()->numberBetween(10, 50_000),
            'premium_tier' => fake()->numberBetween(0, 3),
            'features' => [],
        ];
    }
}
