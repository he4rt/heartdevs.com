<?php

declare(strict_types=1);

namespace He4rt\Integrations\Twitch\OAuth\DTO;

use He4rt\Authentication\DTO\OAuthAccessDTO;
use He4rt\Authentication\DTO\OAuthUserDTO;
use He4rt\Authentication\Enums\OAuthProviderEnum;

final class TwitchOAuthDTO extends OAuthUserDTO
{
    public static function make(OAuthAccessDTO $credentials, array $payload): self
    {
        $user = $payload['data'][0];

        return new self(
            credentials: $credentials,
            providerId: $user['id'],
            provider: OAuthProviderEnum::Twitch,
            username: $user['login'],
            name: $user['display_name'],
            email: $user['email'],
            avatarUrl: $user['profile_image_url']
        );
    }
}
