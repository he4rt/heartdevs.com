<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final class ResolveExternalIdentity
{
    public function handle(ResolveUserProviderDTO $dto): ExternalIdentity
    {
        return ExternalIdentity::query()->firstOrCreate(
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
