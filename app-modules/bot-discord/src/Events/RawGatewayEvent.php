<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Events;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;

final class RawGatewayEvent
{
    public function handle(object $payload): void
    {
        if (!isset($payload->t, $payload->d)) {
            return;
        }

        DiscordEventLog::query()->create([
            'event_type' => $payload->t,
            'guild_id' => $payload->d->guild_id ?? null,
            'user_id' => $payload->d->user_id ?? $payload->d->author->id ?? null,
            'channel_id' => $payload->d->channel_id ?? null,
            'payload' => json_decode(json_encode($payload->d), true),
            'occurred_at' => now(),
        ]);
    }
}
