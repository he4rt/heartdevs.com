<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(RefreshDatabase::class);

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

test('links a twitch channel to a tenant', function (): void {
    mockHelixUsersResponse();
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-developers']);

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt', '--tenant' => 'he4rt-developers'])
        ->assertSuccessful();

    $identity = ExternalIdentity::query()
        ->where('provider', IdentityProvider::Twitch)
        ->where('external_account_id', '12345')
        ->first();

    expect($identity)->not->toBeNull()
        ->and($identity->tenant_id)->toBe($tenant->id)
        ->and($identity->metadata)->toMatchArray([
            'login' => 'danielhe4rt',
            'display_name' => 'danielhe4rt',
        ]);
});

test('warns when channel is already linked', function (): void {
    mockHelixUsersResponse();
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-developers']);

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt', '--tenant' => 'he4rt-developers'])
        ->assertSuccessful();

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt', '--tenant' => 'he4rt-developers'])
        ->assertSuccessful();

    expect(ExternalIdentity::query()->where('provider', IdentityProvider::Twitch)->count())->toBe(1);
});

test('fails when twitch user is not found', function (): void {
    $mock = new MockClient([
        '*' => MockResponse::make(['data' => []]),
    ]);

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(appToken: 'fake-token', clientId: 'fake-client-id'),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    ));

    Tenant::factory()->create(['slug' => 'he4rt-developers']);

    $this->artisan('twitch:link-channel', ['login' => 'nonexistent', '--tenant' => 'he4rt-developers'])
        ->assertFailed();
});

test('fails when tenant is not found', function (): void {
    mockHelixUsersResponse();

    $this->artisan('twitch:link-channel', ['login' => 'danielhe4rt', '--tenant' => 'nonexistent'])
        ->assertFailed();
});
