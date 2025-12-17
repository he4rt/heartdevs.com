<?php

declare(strict_types=1);

namespace He4rt\Provider\DTO;

use He4rt\Provider\Enums\ProviderEnum;

class ResolveUserProviderDTO
{
    public function __construct(
        public int $tenantId,
        public ProviderEnum $provider,
        public string $providerId,
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
            providerId: $data['provider_id'],
            modelType: $data['model_type'],
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }
}
