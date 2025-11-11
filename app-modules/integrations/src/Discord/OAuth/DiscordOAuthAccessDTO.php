<?php

declare(strict_types=1);

namespace He4rt\Integrations\Discord\OAuth;

use He4rt\Authentication\DTO\OAuthAccessDTO;

final class DiscordOAuthAccessDTO extends OAuthAccessDTO
{
    public static function make(array $payload): OAuthAccessDTO
    {
        return new self(
            accessToken: $payload['access_token'],
            refreshToken: $payload['refresh_token'],
            expiresIn: $payload['expires_in']
        );
    }
}
