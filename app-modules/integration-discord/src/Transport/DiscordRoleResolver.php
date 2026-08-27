<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\Transport;

use He4rt\IntegrationDiscord\Transport\Requests\Members\GetMember;
use Throwable;

final readonly class DiscordRoleResolver
{
    public function __construct(
        private DiscordConnector $connector,
    ) {}

    public function resolveProtectionTier(string $guildId, string $userId): ?string
    {
        try {
            $response = $this->connector->send(new GetMember($guildId, $userId));

            if ($response->failed()) {
                return null;
            }

            /** @var array<int, string> $roles */
            $roles = $response->json('roles', []);

            $adminRoleIds = config('he4rt.discord.moderation.admin_role_ids', []);
            $modRoleIds = config('he4rt.discord.moderation.mod_role_ids', []);

            if (array_intersect($roles, $adminRoleIds) !== []) {
                return 'admin';
            }

            if (array_intersect($roles, $modRoleIds) !== []) {
                return 'mod';
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
