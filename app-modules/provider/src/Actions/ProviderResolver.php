<?php

declare(strict_types=1);

namespace He4rt\Provider\Actions;

use He4rt\Provider\DTO\ResolveUserProviderDTO;
use He4rt\Provider\Models\Provider;

final class ProviderResolver
{
    public function handle(ResolveUserProviderDTO $dto): Provider
    {
        return Provider::query()->firstOrCreate(
            [
                'provider' => $dto->provider,
                'tenant_id' => $dto->tenantId,
                'provider_id' => $dto->providerId,
                'model_type' => $dto->modelType,
            ],
            [
                'username' => $dto->username,
                'email' => $dto->email,
                'avatar' => $dto->avatar,
            ]
        );
    }
}
