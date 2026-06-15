<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Sync\Actions;

use He4rt\IntegrationDiscord\Enums\DiscordChannelType;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Channels\ListGuildChannels;
use He4rt\IntegrationDiscord\Transport\Requests\Guilds\GetGuild;
use He4rt\IntegrationDiscord\Transport\Requests\Members\ListGuildMembers;
use He4rt\IntegrationDiscord\Transport\Requests\Roles\ListGuildRoles;

final readonly class SyncDiscordGuildAction
{
    public function __construct(
        private DiscordConnector $connector,
    ) {}

    public function execute(string $discordGuildId, bool $fresh = false): DiscordGuild
    {
        $guild = $this->syncGuild($discordGuildId, $fresh);
        $this->syncChannels($guild, $discordGuildId);
        $roleMap = $this->syncRoles($guild, $discordGuildId);
        $this->syncMembers($guild, $discordGuildId, $roleMap);

        $guild->update(['synced_at' => now()]);

        return $guild->refresh();
    }

    private function syncGuild(string $discordGuildId, bool $fresh): DiscordGuild
    {
        if ($fresh) {
            DiscordGuild::query()->where('discord_guild_id', $discordGuildId)->delete();
        }

        $response = $this->connector->send(new GetGuild($discordGuildId));

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return DiscordGuild::query()->updateOrCreate(
            ['discord_guild_id' => $discordGuildId],
            [
                'name' => $data['name'],
                'icon' => $data['icon'] ?? null,
                'description' => $data['description'] ?? null,
                'member_count' => $data['approximate_member_count'] ?? null,
                'premium_tier' => $data['premium_tier'] ?? 0,
                'features' => $data['features'] ?? [],
            ],
        );
    }

    private function syncChannels(DiscordGuild $guild, string $discordGuildId): void
    {
        $response = $this->connector->send(new ListGuildChannels($discordGuildId));

        /** @var array<int, array<string, mixed>> $channels */
        $channels = $response->json();

        $channelIdMap = [];

        foreach ($channels as $channelData) {
            $channel = DiscordChannel::query()->updateOrCreate(
                ['discord_channel_id' => $channelData['id']],
                [
                    'discord_guild_id' => $guild->id,
                    'name' => $channelData['name'] ?? '',
                    'type' => DiscordChannelType::tryFrom($channelData['type'] ?? 0) ?? DiscordChannelType::GuildText,
                    'topic' => $channelData['topic'] ?? null,
                    'position' => $channelData['position'] ?? 0,
                    'nsfw' => $channelData['nsfw'] ?? false,
                    'bitrate' => $channelData['bitrate'] ?? null,
                    'user_limit' => $channelData['user_limit'] ?? null,
                ],
            );

            $channelIdMap[$channelData['id']] = [
                'internal_id' => $channel->id,
                'parent_discord_id' => $channelData['parent_id'] ?? null,
            ];
        }

        foreach ($channelIdMap as $mapping) {
            if ($mapping['parent_discord_id'] !== null && isset($channelIdMap[$mapping['parent_discord_id']])) {
                DiscordChannel::query()
                    ->where('id', $mapping['internal_id'])
                    ->update(['parent_id' => $channelIdMap[$mapping['parent_discord_id']]['internal_id']]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function syncRoles(DiscordGuild $guild, string $discordGuildId): array
    {
        $response = $this->connector->send(new ListGuildRoles($discordGuildId));

        /** @var array<int, array<string, mixed>> $roles */
        $roles = $response->json();

        $roleMap = [];

        foreach ($roles as $roleData) {
            $role = DiscordRole::query()->updateOrCreate(
                ['discord_role_id' => $roleData['id']],
                [
                    'discord_guild_id' => $guild->id,
                    'name' => $roleData['name'],
                    'color' => $roleData['color'] ?? 0,
                    'position' => $roleData['position'] ?? 0,
                    'permissions' => (int) ($roleData['permissions'] ?? 0),
                    'is_hoisted' => $roleData['hoist'] ?? false,
                    'is_mentionable' => $roleData['mentionable'] ?? false,
                    'is_managed' => $roleData['managed'] ?? false,
                    'icon' => $roleData['icon'] ?? null,
                ],
            );

            $roleMap[$roleData['id']] = $role->id;
        }

        return $roleMap;
    }

    /**
     * @param  array<string, int>  $roleMap
     */
    private function syncMembers(DiscordGuild $guild, string $discordGuildId, array $roleMap): void
    {
        $after = null;

        do {
            $response = $this->connector->send(new ListGuildMembers($discordGuildId, 1_000, $after));

            /** @var array<int, array<string, mixed>> $members */
            $members = $response->json();

            foreach ($members as $memberData) {
                $user = $memberData['user'] ?? [];
                $userId = $user['id'] ?? null;

                if ($userId === null) {
                    continue;
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
                        'nickname' => $memberData['nick'] ?? null,
                        'is_bot' => $user['bot'] ?? false,
                        'is_pending' => $memberData['pending'] ?? false,
                        'joined_at' => $memberData['joined_at'] ?? null,
                        'premium_since' => $memberData['premium_since'] ?? null,
                        'communication_disabled_until' => $memberData['communication_disabled_until'] ?? null,
                    ],
                );

                /** @var array<int, string> $roleIds */
                $roleIds = $memberData['roles'] ?? [];

                $memberRoleIds = collect($roleIds)
                    ->filter(fn (string $roleId): bool => isset($roleMap[$roleId]))
                    ->mapWithKeys(fn (string $roleId): array => [
                        $roleMap[$roleId] => ['assigned_at' => now()],
                    ])
                    ->all();

                $member->roles()->sync($memberRoleIds);

                $after = $userId;
            }
        } while (count($members) === 1_000);
    }
}
