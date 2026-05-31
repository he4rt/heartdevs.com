<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\OAuth\DiscordOAuthAccessDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthUser;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('exchanges code for token and returns DiscordOAuthAccessDTO', function (): void {
    $mockClient = new MockClient([
        ExchangeCodeForToken::class => MockResponse::make([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 604800,
        ]),
    ]);

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient($mockClient);

    $client = new DiscordOAuthClient($connector);
    $result = $client->auth('test-code');

    expect($result)
        ->toBeInstanceOf(DiscordOAuthAccessDTO::class)
        ->and($result->accessToken)->toBe('test-access-token')
        ->and($result->refreshToken)->toBe('test-refresh-token')
        ->and($result->expiresIn)->toBe(604800);
});

it('gets authenticated user and returns DiscordOAuthUser', function (): void {
    $mockClient = new MockClient([
        GetCurrentUser::class => MockResponse::make([
            'id' => '123456789',
            'username' => 'testuser',
            'global_name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'abc123',
        ]),
    ]);

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient($mockClient);

    $credentials = DiscordOAuthAccessDTO::make([
        'access_token' => 'test-access-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 604800,
    ]);

    $client = new DiscordOAuthClient($connector);
    $result = $client->getAuthenticatedUser($credentials);

    expect($result)
        ->toBeInstanceOf(DiscordOAuthUser::class)
        ->and($result->username)->toBe('testuser')
        ->and($result->name)->toBe('Test User')
        ->and($result->email)->toBe('test@example.com')
        ->and($result->providerId)->toBe('123456789');
});

it('generates correct redirect url', function (): void {
    config()->set('services.discord.scopes', 'identify email');
    config()->set('app.url', 'http://localhost:8000');

    $connector = new DiscordOAuthConnector('my-client-id', 'client-secret', 'https://example.com/callback');
    $client = new DiscordOAuthClient($connector);

    $url = $client->redirectUrl();

    $expectedCallback = urlencode('http://localhost:8000/auth/oauth/discord');

    expect($url)
        ->toContain('https://discord.com/oauth2/authorize')
        ->toContain('client_id=my-client-id')
        ->toContain('response_type=code')
        ->toContain('redirect_uri='.$expectedCallback)
        ->toContain('scope=identify+email');
});
