<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Database\Factories;

use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordRole>
 */
final class DiscordRoleFactory extends Factory
{
    protected $model = DiscordRole::class;

    public function definition(): array
    {
        return [
            'discord_guild_id' => DiscordGuild::factory(),
            'discord_role_id' => (string) fake()->unique()->numberBetween(100000000000000000, 999999999999999999),
            'name' => fake()->word(),
            'color' => fake()->numberBetween(0, 16777215),
            'position' => fake()->numberBetween(0, 50),
            'permissions' => 0,
            'is_hoisted' => false,
            'is_mentionable' => false,
            'is_managed' => false,
        ];
    }
}
