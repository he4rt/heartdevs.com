<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Handlers;

use He4rt\IntegrationDiscord\Enums\RoleHistoryAction;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Models\DiscordMemberRoleHistory;
use He4rt\IntegrationDiscord\Models\DiscordRole;

final class HandleAuditLogEntry
{
    private const int ACTION_TYPE_MEMBER_ROLE_UPDATE = 25;

    public function handle(DiscordEventLog $eventLog): void
    {
        $payload = $eventLog->payload;

        if (($payload['action_type'] ?? null) !== self::ACTION_TYPE_MEMBER_ROLE_UPDATE) {
            return;
        }

        $guildDiscordId = $payload['guild_id'] ?? $eventLog->guild_id;
        $targetUserId = $payload['target_id'] ?? null;

        if ($guildDiscordId === null || $targetUserId === null) {
            return;
        }

        $guild = DiscordGuild::query()->where('discord_guild_id', $guildDiscordId)->first();

        if ($guild === null) {
            return;
        }

        $member = DiscordMember::query()
            ->where('discord_guild_id', $guild->id)
            ->where('discord_user_id', $targetUserId)
            ->first();

        if ($member === null) {
            return;
        }

        foreach ($payload['changes'] ?? [] as $change) {
            $this->processChange($member, $guild, $change, $eventLog);
        }
    }

    /**
     * @param  array<string, mixed>  $change
     */
    private function processChange(DiscordMember $member, DiscordGuild $guild, array $change, DiscordEventLog $eventLog): void
    {
        $key = $change['key'] ?? null;

        if ($key === '$add') {
            $this->recordRoleChanges($member, $guild, $change['new_value'] ?? [], RoleHistoryAction::Assigned, $eventLog);
        } elseif ($key === '$remove') {
            $this->recordRoleChanges($member, $guild, $change['new_value'] ?? [], RoleHistoryAction::Removed, $eventLog);
        }
    }

    /**
     * @param  array<int, array{id: string, name: string}>  $roles
     */
    private function recordRoleChanges(
        DiscordMember $member,
        DiscordGuild $guild,
        array $roles,
        RoleHistoryAction $action,
        DiscordEventLog $eventLog,
    ): void {
        foreach ($roles as $roleData) {
            $role = DiscordRole::query()
                ->where('discord_guild_id', $guild->id)
                ->where('discord_role_id', $roleData['id'])
                ->first();

            if ($role === null) {
                continue;
            }

            DiscordMemberRoleHistory::query()->create([
                'discord_member_id' => $member->id,
                'discord_role_id' => $role->id,
                'action' => $action,
                'occurred_at' => now(),
                'source_event_log_id' => $eventLog->id,
            ]);
        }
    }
}
