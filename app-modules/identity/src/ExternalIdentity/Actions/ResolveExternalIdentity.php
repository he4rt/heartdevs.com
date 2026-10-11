<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\DTOs\ResolveUserProviderDTO;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final class ResolveExternalIdentity
{
    /**
     * Resolve ou cria uma identidade externa e sincroniza o username caso esteja ausente ou alterado.
     *
     * @param  ResolveUserProviderDTO  $dto
     * @return ExternalIdentity
     */
    public function handle(ResolveUserProviderDTO $dto): ExternalIdentity
    {
        $identity = ExternalIdentity::query()->firstOrCreate(
            [
                'provider' => $dto->provider,
                'external_account_id' => $dto->externalAccountId,
                'model_type' => $dto->modelType,
            ],
            [
                'type' => $dto->provider->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => ClientAccessManager::make(),
                'metadata' => array_filter([
                    'username' => $dto->username,
                    'email' => $dto->email,
                    'avatar' => $dto->avatar,
                ]),
            ]
        );

        $trimmedUsername = is_string($dto->username) ? trim($dto->username) : null;
        if ($trimmedUsername !== null && $trimmedUsername !== '' && ($identity->metadata['username'] ?? null) !== $trimmedUsername) {
            $metadata = $identity->metadata ?? [];
            $metadata['username'] = $trimmedUsername;
            $identity->update(['metadata' => $metadata]);
        }

        return $identity;
    }
}
