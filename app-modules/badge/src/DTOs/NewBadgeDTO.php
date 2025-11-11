<?php

declare(strict_types=1);

namespace He4rt\Badge\DTOs;

use JsonSerializable;

final readonly class NewBadgeDTO implements JsonSerializable
{
    public function __construct(
        private string $provider,
        private string $name,
        private string $description,
        private string $redeemCode,
        private bool $active,
        private int $tenant_id
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            provider: $payload['provider'],
            name: $payload['name'],
            description: $payload['description'],
            redeemCode: $payload['redeem_code'],
            active: $payload['active'],
            tenant_id: $payload['tenant_id']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'name' => $this->name,
            'description' => $this->description,
            'redeem_code' => $this->redeemCode,
            'active' => $this->active,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
