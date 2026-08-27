<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Database\Factories;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordEventLog>
 */
final class DiscordEventLogFactory extends Factory
{
    protected $model = DiscordEventLog::class;

    public function definition(): array
    {
        return [
            'event_type' => fake()->randomElement(['MESSAGE_CREATE', 'GUILD_MEMBER_ADD', 'VOICE_STATE_UPDATE', 'GUILD_BAN_ADD']),
            'guild_id' => (string) fake()->numerify('45292621755816####'),
            'user_id' => (string) fake()->numerify('26749964733605####'),
            'channel_id' => (string) fake()->numerify('55922413525165####'),
            'payload' => ['id' => fake()->uuid(), 'content' => fake()->sentence()],
        ];
    }
}
