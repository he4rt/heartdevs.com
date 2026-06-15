<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

final readonly class DiscordProfileDTO
{
    /**
     * @param  array<int, ConnectedAccountDTO>  $connectedAccounts
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $discordId,
        public string $username,
        public string $name,
        public ?string $joinedAt,
        public array $connectedAccounts,
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $profile
     */
    public static function fromDump(array $profile): self
    {
        $user = $profile['user'] ?? [];
        $guildMember = $profile['guild_member'] ?? [];

        return new self(
            discordId: $user['id'],
            username: $user['username'] ?? $user['id'],
            name: $user['global_name'] ?? $user['username'] ?? $user['id'],
            joinedAt: $guildMember['joined_at'] ?? null,
            connectedAccounts: array_values(array_map(
                ConnectedAccountDTO::fromDump(...),
                $profile['connected_accounts'] ?? [],
            )),
            metadata: $profile,
        );
    }
}
