<?php

declare(strict_types=1);

namespace He4rt\IntegrationWhatsapp\Database\Factories;

use He4rt\IntegrationWhatsapp\Models\WhatsAppEventLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WhatsAppEventLog>
 */
final class WhatsAppEventLogFactory extends Factory
{
    protected $model = WhatsAppEventLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => (string) Str::uuid(),
            'type' => 'messages.upsert',
            'chat_jid' => fake()->numerify('##################').'@g.us',
            'received_at' => now(),
            'payload' => [
                'key' => ['id' => (string) Str::uuid()],
                'message' => ['conversation' => fake()->sentence()],
            ],
        ];
    }
}
