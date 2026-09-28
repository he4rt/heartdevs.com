<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthConnectionDTO;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

final class PersistOAuthConnection
{
    public function execute(User $owner, OAuthConnectionDTO $connection, ?string $connectedBy): ExternalIdentity
    {
        /** @var ExternalIdentity $identity */
        $identity = $owner->providers()->firstOrNew([
            'provider' => $connection->provider,
            'external_account_id' => $connection->providerId,
        ]);

        $identity->forceFill([
            'type' => $connection->provider->getType(),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => $connection->credentials,
            'metadata' => array_replace($identity->metadata ?? [], $connection->metadata),
            'connected_at' => now(),
            'disconnected_at' => null,
            'connected_by' => $connectedBy,
        ])->save();

        event(new ExternalIdentityConnected($identity));

        return $identity;
    }
}
