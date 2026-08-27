<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\OAuth;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class DiscordOAuthUser extends OAuthUserDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(OAuthAccessDTO $credentials, array $payload): OAuthUserDTO
    {
        return new self(
            credentials: $credentials,
            providerId: $payload['id'],
            provider: IdentityProvider::Discord,
            username: $payload['username'],
            name: $payload['global_name'] ?? $payload['username'],
            email: $payload['email'],
            avatarUrl: sprintf('https://cdn.discordapp.com/avatars/%s/%s.png', $payload['id'], $payload['avatar']),
        );
    }
}
