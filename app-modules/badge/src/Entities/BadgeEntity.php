<?php

declare(strict_types=1);

namespace He4rt\Badge\Entities;

use JsonSerializable;

final readonly class BadgeEntity implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $redeemCode,
        public bool $active,
        public int $tenantId
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            name: $payload['name'],
            description: $payload['description'],
            redeemCode: $payload['redeem_code'],
            active: $payload['active'],
            tenantId: $payload['tenant_id']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
