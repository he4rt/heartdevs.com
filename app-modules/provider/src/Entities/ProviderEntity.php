<?php

declare(strict_types=1);

namespace He4rt\Provider\Entities;

use He4rt\Provider\ValueObjects\ProviderData;
use He4rt\User\Models\User;

final readonly class ProviderEntity
{
    public ProviderData $provider;

    public function __construct(
        public string $id,
        public string $modelType,
        public string $modelId,
        string $provider,
        string $providerId
    ) {
        $this->provider = new ProviderData($provider, $providerId);
    }

    public static function make(array $payload): self
    {
        return new self(
            $payload['id'],
            User::class,
            $payload['model_id'],
            $payload['provider'],
            $payload['provider_id'],
            $payload['email'] ?? null,
        );
    }
}
