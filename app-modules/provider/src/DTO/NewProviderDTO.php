<?php

declare(strict_types=1);

namespace He4rt\Provider\DTO;

use He4rt\Provider\Enums\ProviderEnum;
use JsonSerializable;

final readonly class NewProviderDTO implements JsonSerializable
{
    public function __construct(
        private int $tenantId,
        private ProviderEnum $provider,
        private string $providerId
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'provider' => $this->provider->value,
            'provider_id' => $this->providerId,
        ];
    }
}
