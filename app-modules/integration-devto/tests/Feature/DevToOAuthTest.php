<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationDevTo\OAuth\DevToOAuthAccessDTO;
use He4rt\IntegrationDevTo\OAuth\DevToOAuthClient;
use He4rt\IntegrationDevTo\OAuth\DevToOAuthUser;
use Illuminate\Support\Facades\Http;

test('generates redirect url with correct params', function (): void {
    config([
        'services.devto.client_id' => 'test-client-id',
        'services.devto.redirect_uri' => 'https://example.com/callback',
        'services.devto.scopes' => 'public',
    ]);

    $client = new DevToOAuthClient();
    $url = $client->redirectUrl();

    expect($url)->toContain('https://dev.to/oauth/authorize')
        ->and($url)->toContain('client_id=test-client-id')
        ->and($url)->toContain('response_type=code')
        ->and($url)->toContain('scope=public');
});

test('exchanges code for access token', function (): void {
    Http::fake([
        'dev.to/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 3_600,
        ]),
    ]);

    $client = new DevToOAuthClient();
    $dto = $client->auth('test-code');

    expect($dto)->toBeInstanceOf(DevToOAuthAccessDTO::class)
        ->and($dto->accessToken)->toBe('test-access-token')
        ->and($dto->refreshToken)->toBe('test-refresh-token');
});

test('fetches authenticated user info', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => 12_345,
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'profile_image' => 'https://dev.to/avatar.png',
        ]),
    ]);

    $accessDTO = DevToOAuthAccessDTO::make([
        'access_token' => 'token',
        'refresh_token' => 'refresh',
        'expires_in' => 3_600,
    ]);

    $client = new DevToOAuthClient();
    $user = $client->getAuthenticatedUser($accessDTO);

    expect($user)->toBeInstanceOf(DevToOAuthUser::class)
        ->and($user->providerId)->toBe('12345')
        ->and($user->provider)->toBe(IdentityProvider::DevTo)
        ->and($user->username)->toBe('testuser')
        ->and($user->name)->toBe('Test User');
});
