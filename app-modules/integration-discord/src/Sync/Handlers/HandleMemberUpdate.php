<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Handlers;

use He4rt\IntegrationDiscord\Enums\RoleHistoryAction;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Models\DiscordMemberRoleHistory;
use He4rt\IntegrationDiscord\Models\DiscordRole;

final class HandleMemberUpdate
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
        $userId = $user['id'] ?? $eventLog->user_id;

        if ($userId === null) {
            return;
        }

        $member = DiscordMember::query()->updateOrCreate(
            [
                'discord_guild_id' => $guild->id,
                'discord_user_id' => $userId,
            ],
            [
                'username' => $user['username'] ?? 'unknown',
                'global_name' => $user['global_name'] ?? null,
                'avatar' => $user['avatar'] ?? null,
                'nickname' => $payload['nick'] ?? null,
                'is_bot' => $user['bot'] ?? false,
                'is_pending' => $payload['pending'] ?? false,
                'joined_at' => $payload['joined_at'] ?? null,
                'premium_since' => $payload['premium_since'] ?? null,
                'communication_disabled_until' => $payload['communication_disabled_until'] ?? null,
            ],
        );

        $this->syncRoles($member, $guild, $payload['roles'] ?? [], $eventLog);
    }

    /**
     * @param  array<int, string>  $newRoleIds
     */
    private function syncRoles(DiscordMember $member, DiscordGuild $guild, array $newRoleIds, DiscordEventLog $eventLog): void
    {
        $roleMap = DiscordRole::query()
            ->where('discord_guild_id', $guild->id)
            ->whereIn('discord_role_id', $newRoleIds)
            ->pluck('id', 'discord_role_id');

        $currentRoleIds = $member->roles()->pluck('discord_roles.id')->all();
        $newInternalIds = $roleMap->values()->all();

        $added = array_diff($newInternalIds, $currentRoleIds);
        $removed = array_diff($currentRoleIds, $newInternalIds);

        foreach ($added as $roleId) {
            $member->roles()->attach($roleId, ['assigned_at' => now()]);

            DiscordMemberRoleHistory::query()->create([
                'discord_member_id' => $member->id,
                'discord_role_id' => $roleId,
                'action' => RoleHistoryAction::Assigned,
                'occurred_at' => now(),
                'source_event_log_id' => $eventLog->id,
            ]);
        }

        foreach ($removed as $roleId) {
            $member->roles()->detach($roleId);

            DiscordMemberRoleHistory::query()->create([
                'discord_member_id' => $member->id,
                'discord_role_id' => $roleId,
                'action' => RoleHistoryAction::Removed,
                'occurred_at' => now(),
                'source_event_log_id' => $eventLog->id,
            ]);
        }
    }
}
