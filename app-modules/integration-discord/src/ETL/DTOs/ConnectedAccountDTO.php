<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\ETL\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class ConnectedAccountDTO
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public IdentityProvider $provider,
        public string $externalAccountId,
        public string $name,
        public bool $verified,
        public array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $account
     */
    public static function fromDump(array $account): self
    {
        return new self(
            provider: IdentityProvider::from($account['type']),
            externalAccountId: $account['id'],
            name: $account['name'],
            verified: $account['verified'] ?? false,
            metadata: $account,
        );
    }
}
