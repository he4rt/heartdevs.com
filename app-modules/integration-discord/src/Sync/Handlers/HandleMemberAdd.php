<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Handlers;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;

final class HandleMemberAdd
{
    public function handle(DiscordEventLog $eventLog): void
    {
        $payload = $eventLog->payload;
        $guildDiscordId = $payload['guild_id'] ?? $eventLog->guild_id;

        if ($guildDiscordId === null) {
            return;
        }

        $guild = DiscordGuild::query()->where('discord_guild_id', $guildDiscordId)->first();

        if ($guild === null) {
            return;
        }

        $user = $payload['user'] ?? [];

        DiscordMember::query()->updateOrCreate(
            [
                'discord_guild_id' => $guild->id,
                'discord_user_id' => $user['id'] ?? $eventLog->user_id,
            ],
            [
                'username' => $user['username'] ?? 'unknown',
                'global_name' => $user['global_name'] ?? null,
                'avatar' => $user['avatar'] ?? null,
                'is_bot' => $user['bot'] ?? false,
                'is_pending' => $payload['pending'] ?? false,
                'joined_at' => $payload['joined_at'] ?? null,
                'left_at' => null,
            ],
        );
    }
}
