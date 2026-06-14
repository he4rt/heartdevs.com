<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use RuntimeException;

final class RawGatewayEvent
{
    public function handle(object $payload): void
    {
        if (!isset($payload->t, $payload->d)) {
            return;
        }

        $encodedPayload = json_encode($payload->d);

        throw_if($encodedPayload === false, RuntimeException::class, 'Failed to encode Discord gateway payload to JSON.');

        DiscordEventLog::query()->create([
            'event_type' => $payload->t,
            'guild_id' => $payload->d->guild_id ?? null,
            'user_id' => $payload->d->user_id ?? $payload->d->author->id ?? null,
            'channel_id' => $payload->d->channel_id ?? null,
            'payload' => json_decode($encodedPayload, true),
        ]);
    }
}
