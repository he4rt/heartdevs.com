<?php

declare(strict_types=1);

namespace He4rt\IntegrationDevTo\OAuth;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class DevToOAuthUser extends OAuthUserDTO
{
    public static function make(OAuthAccessDTO $credentials, array $payload): OAuthUserDTO
    {
        return new self(
            credentials: $credentials,
            providerId: (string) $payload['id'],
            provider: IdentityProvider::DevTo,
            username: $payload['username'],
            name: $payload['name'] ?? $payload['username'],
            email: $payload['email'] ?? null,
            avatarUrl: $payload['profile_image'] ?? null,
        );
    }
}
