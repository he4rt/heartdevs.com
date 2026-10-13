<?php

declare(strict_types=1);

use He4rt\Identity\Auth\DTOs\OAuthConnectionDTO;
use He4rt\IntegrationTwitch\OAuth\DTO\TwitchOAuthAccessDTO;
use He4rt\IntegrationTwitch\OAuth\DTO\TwitchOAuthDTO;

test('a conexão da Twitch guarda os escopos que o usuário concedeu', function (): void {
    $access = TwitchOAuthAccessDTO::make([
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'expires_in' => 14_400,
        'scope' => ['user:read:email', 'bits:read'],
    ]);

    $user = TwitchOAuthDTO::make($access, ['data' => [[
        'id' => '12345',
        'login' => 'streamer',
        'display_name' => 'Streamer',
        'email' => 'streamer@example.com',
        'profile_image_url' => null,
    ]]]);

    $connection = OAuthConnectionDTO::fromOAuth($user, $access);

    expect($connection->metadata['granted_scopes'])->toBe(['user:read:email', 'bits:read'])
        ->and($connection->metadata['username'])->toBe('streamer');
});
