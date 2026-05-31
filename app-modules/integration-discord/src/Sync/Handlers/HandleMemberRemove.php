<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Handlers;

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;

final class HandleMemberRemove
{
    public function handle(DiscordEventLog $eventLog): void
    {
        $payload = $eventLog->payload;
        $guildDiscordId = $payload['guild_id'] ?? $eventLog->guild_id;
        $userId = $payload['user']['id'] ?? $eventLog->user_id;

        if ($guildDiscordId === null || $userId === null) {
            return;
        }

        $guild = DiscordGuild::query()->where('discord_guild_id', $guildDiscordId)->first();

        if ($guild === null) {
            return;
        }

        $member = DiscordMember::query()
            ->where('discord_guild_id', $guild->id)
            ->where('discord_user_id', $userId)
            ->first();

        if ($member === null) {
            return;
        }

        $member->update(['left_at' => now()]);
        $member->roles()->detach();
    }
}
