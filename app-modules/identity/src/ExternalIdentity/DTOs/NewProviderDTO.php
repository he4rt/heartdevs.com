<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use JsonSerializable;

final readonly class NewProviderDTO implements JsonSerializable
{
    public function __construct(
        private string $tenantId,
        private IdentityProvider $provider,
        private string $externalAccountId
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'provider' => $this->provider->value,
            'external_account_id' => $this->externalAccountId,
        ];
    }
}
