<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Handlers;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;

final class HandleVoiceStateUpdate
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

        $member = $payload['member'] ?? [];
        $user = $member['user'] ?? [];
        $userId = $payload['user_id'] ?? $user['id'] ?? $eventLog->user_id;

        if ($userId === null) {
            return;
        }

        DiscordMember::query()->updateOrCreate(
            [
                'discord_guild_id' => $guild->id,
                'discord_user_id' => $userId,
            ],
            [
                'username' => $user['username'] ?? 'unknown',
                'global_name' => $user['global_name'] ?? null,
                'avatar' => $user['avatar'] ?? null,
                'is_bot' => $user['bot'] ?? false,
                'is_pending' => $member['pending'] ?? false,
                'joined_at' => $member['joined_at'] ?? null,
            ],
        );
    }
}
