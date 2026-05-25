<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Actions;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

final class AttachProviderToUser
{
    public function execute(User|Tenant $owner, Tenant $tenant, OAuthUserDTO $oauthUser, OAuthAccessDTO $access): ExternalIdentity
    {
        /** @var ExternalIdentity */
        return $owner->providers()->updateOrCreate(
            [
                'tenant_id' => $tenant->getKey(),
                'provider' => $oauthUser->provider,
                'external_account_id' => $oauthUser->providerId,
            ],
            [
                'type' => $oauthUser->provider->getType(),
                'credentials_type' => CredentialsType::OAuth2,
                'credentials' => $access->toClientAccessManager(),
                'metadata' => array_filter([
                    'email' => $oauthUser->email,
                    'avatar' => $oauthUser->avatarUrl,
                    'username' => $oauthUser->username,
                ]),
                'connected_at' => now(),
                'connected_by' => auth()->id(),
            ]
        );
    }
}
