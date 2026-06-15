<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use RuntimeException;

final class RawGatewayEvent
{
    /** @param object{t?: string, d?: object} $payload */
    public function handle(object $payload): void
    {
        if (!isset($payload->t, $payload->d)) {
            return;
        }

        /** @var object{guild_id?: string, user_id?: string, channel_id?: string} $data */
        $data = $payload->d;

        $encodedPayload = json_encode($data);

        throw_if($encodedPayload === false, RuntimeException::class, 'Failed to encode Discord gateway payload to JSON.');

        DiscordEventLog::query()->create([
            'event_type' => $payload->t,
            'guild_id' => $data->guild_id ?? null,
            'user_id' => $data->user_id ?? data_get($payload->d, 'author.id'),
            'channel_id' => $data->channel_id ?? null,
            'payload' => json_decode($encodedPayload, true),
        ]);
    }
}
