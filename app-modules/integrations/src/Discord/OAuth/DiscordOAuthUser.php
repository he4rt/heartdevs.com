<?php

declare(strict_types=1);

namespace He4rt\Integrations\Discord\OAuth;

use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;
use He4rt\Authentication\Enums\OAuthProviderEnum;

class DiscordOAuthUser extends OAuthUserDTO
{
    public static function make(OAuthAccessDTO $credentials, array $payload): OAuthUserDTO
    {
        return new self(
            credentials: $credentials,
            providerId: $payload['id'],
            provider: OAuthProviderEnum::Discord,
            username: $payload['username'],
            name: $payload['global_name'] ?? $payload['username'],
            email: $payload['email'],
            avatarUrl: sprintf('https://cdn.discordapp.com/avatars/%s/%s.png', $payload['id'], $payload['avatar']),
        );
    }
}
