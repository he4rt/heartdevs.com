<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth\DTO;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final class TwitchOAuthDTO extends OAuthUserDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(OAuthAccessDTO $credentials, array $payload): self
    {
        $user = $payload['data'][0];

        return new self(
            credentials: $credentials,
            providerId: $user['id'],
            provider: IdentityProvider::Twitch,
            username: $user['login'],
            name: $user['display_name'],
            email: $user['email'],
            avatarUrl: $user['profile_image_url']
        );
    }
}
