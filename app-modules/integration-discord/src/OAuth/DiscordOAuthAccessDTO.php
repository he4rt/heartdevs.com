<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord\OAuth;

use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;

final class DiscordOAuthAccessDTO extends OAuthAccessDTO
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresIn: $payload['expires_in']
        );
    }
}
