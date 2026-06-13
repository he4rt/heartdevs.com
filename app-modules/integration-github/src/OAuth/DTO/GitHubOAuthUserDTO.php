<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub\OAuth\DTO;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class GitHubOAuthUserDTO extends OAuthUserDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(OAuthAccessDTO $credentials, array $payload): self
    {
        return new self(
            credentials: $credentials,
            providerId: (string) $payload['id'],
            provider: IdentityProvider::GitHub,
            username: $payload['login'],
            name: $payload['name'] ?? $payload['login'],
            email: $payload['email'],
            avatarUrl: $payload['avatar_url'],
        );
    }
}
