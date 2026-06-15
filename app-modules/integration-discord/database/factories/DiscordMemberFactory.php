<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Database\Factories;

use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordMember>
 */
final class DiscordMemberFactory extends Factory
{
    protected $model = DiscordMember::class;

    public function definition(): array
    {
        return [
            'discord_guild_id' => DiscordGuild::factory(),
            'discord_user_id' => (string) fake()->unique()->numberBetween(100_000_000_000_000_000, 999_999_999_999_999_999),
            'username' => fake()->userName(),
            'global_name' => fake()->optional()->name(),
            'avatar' => fake()->optional()->md5(),
            'is_bot' => false,
            'is_pending' => false,
            'joined_at' => fake()->dateTimeBetween('-2 years'),
        ];
    }

    public function bot(): static
    {
        return $this->state([
            'is_bot' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'is_pending' => true,
        ]);
    }

    public function left(): static
    {
        return $this->state([
            'left_at' => now(),
        ]);
    }
}
