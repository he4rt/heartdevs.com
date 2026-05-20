<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Database\Factories;

use He4rt\IntegrationDiscord\Enums\DiscordChannelType;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordChannel>
 */
final class DiscordChannelFactory extends Factory
{
    protected $model = DiscordChannel::class;

    public function definition(): array
    {
        return [
            'discord_guild_id' => DiscordGuild::factory(),
            'discord_channel_id' => (string) fake()->unique()->numberBetween(100000000000000000, 999999999999999999),
            'name' => fake()->slug(2),
            'type' => DiscordChannelType::GuildText,
            'position' => fake()->numberBetween(0, 50),
            'nsfw' => false,
        ];
    }

    public function voice(): static
    {
        return $this->state([
            'type' => DiscordChannelType::GuildVoice,
            'bitrate' => 64000,
            'user_limit' => 0,
        ]);
    }

    public function category(): static
    {
        return $this->state([
            'type' => DiscordChannelType::GuildCategory,
        ]);
    }
}
