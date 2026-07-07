<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

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

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(appToken: 'fake-token', clientId: 'fake-client-id'),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    ));

    return $mock;
}

test('resolves a twitch channel broadcaster id and prints the env config', function (): void {
    mockHelixUsersResponse();

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt'])
        ->expectsOutputToContain('Broadcaster ID: 12345')
        ->expectsOutputToContain('TWITCH_BROADCASTER_LOGIN=danielhe4rt')
        ->expectsOutputToContain('TWITCH_BROADCASTER_ID=12345')
        ->assertSuccessful();
});

test('fails when twitch user is not found', function (): void {
    $mock = new MockClient([
        '*' => MockResponse::make(['data' => []]),
    ]);

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(appToken: 'fake-token', clientId: 'fake-client-id'),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    ));

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
