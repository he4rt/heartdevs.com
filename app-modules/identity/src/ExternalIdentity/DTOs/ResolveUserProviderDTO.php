<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class ResolveUserProviderDTO
{
    public function __construct(
        public int $tenantId,
        public IdentityProvider $provider,
        public string $externalAccountId,
        public string $modelType,
        public ?string $username = null,
        public ?string $email = null,
        public ?string $avatar = null,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            provider: $data['provider'],
            externalAccountId: $data['external_account_id'],
            modelType: $data['model_type'],
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }
}
