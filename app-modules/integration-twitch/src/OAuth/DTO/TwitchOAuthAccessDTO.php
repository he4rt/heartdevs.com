<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth\DTO;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;

class TwitchOAuthAccessDTO extends OAuthAccessDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        /** @var array<int, string> $grantedScopes */
        $grantedScopes = is_array($payload['scope'] ?? null) ? $payload['scope'] : [];

        return new self(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresIn: $payload['expires_in'],
            grantedScopes: $grantedScopes,
        );
    }
}
