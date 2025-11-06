<?php

declare(strict_types=1);

namespace He4rt\Message\Database\Factories;

use He4rt\Message\Models\Message;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
final class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'provider_id' => Provider::factory(),
            'provider_message_id' => fake()->randomNumber(4),
            'season_id' => 2,
            'channel_id' => fake()->randomNumber(4),
            'content' => fake()->sentence(),
            'sent_at' => now(),
            'obtained_experience' => fake()->randomNumber(2),
        ];
    }
}
