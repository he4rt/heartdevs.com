<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function fakeTwitchHelixConnector(MockClient $mock): TwitchHelixConnector
{
    // The app token is resolved lazily by the connector; seed the cache so
    // getToken() short-circuits without hitting the real Twitch OAuth endpoint.
    Cache::put('twitch_app_access_token', 'fake-token', 3_600);

    return tap(
        new TwitchHelixConnector(
            tokenService: new TwitchAppTokenService(
                new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret'),
            ),
            clientId: 'fake-client-id',
        ),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    );
}

function mockHelixUsersResponse(string $id = '12345', string $login = 'danielhe4rt', string $displayName = 'danielhe4rt'): MockClient
{
    $mock = new MockClient([
        '*' => MockResponse::make([
            'data' => [[
                'id' => $id,
                'login' => $login,
                'display_name' => $displayName,
                'email' => 'test@example.com',
                'profile_image_url' => 'https://example.com/avatar.png',
            ]],
        ]),
    ]);

    app()->instance(TwitchHelixConnector::class, fakeTwitchHelixConnector($mock));

    return $mock;
}

test('resolves a twitch channel broadcaster id and prints the env config', function (): void {
    mockHelixUsersResponse();

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt'])
        ->expectsOutputToContain('Broadcaster ID: 12345')
        ->expectsOutputToContain('php artisan twitch:subscribe 12345 --all')
        ->assertSuccessful();
});

test('fails when twitch user is not found', function (): void {
    $mock = new MockClient([
        '*' => MockResponse::make(['data' => []]),
    ]);

    app()->instance(TwitchHelixConnector::class, fakeTwitchHelixConnector($mock));

    $this->artisan('twitch:link-channel', ['login' => 'nonexistent'])
        ->assertFailed();
});

test('fails when no login is provided and no broadcaster login is configured', function (): void {
    // Bind a connector instance so resolving the command does not build the real
    // one from (absent) Twitch credentials; the command fails before it ever sends.
    mockHelixUsersResponse();
    config()->set('services.twitch.broadcaster_login', '');

    $this->artisan('twitch:link-channel')
        ->assertFailed();
});
