<?php

declare(strict_types=1);

namespace He4rt\Activity\Database\Factories;

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
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
            'external_identity_id' => ExternalIdentity::factory(),
            'provider_message_id' => fake()->randomNumber(4),
            'channel_id' => fake()->randomNumber(4),
            'content' => fake()->sentence(),
            'sent_at' => now(),
            'obtained_experience' => fake()->randomNumber(2),
        ];
    }
}
