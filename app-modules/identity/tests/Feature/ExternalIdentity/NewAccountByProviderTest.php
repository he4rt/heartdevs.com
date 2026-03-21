<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Symfony\Component\HttpFoundation\Response;

test('can create account by provider', function (): void {
    $provider = 'discord';
    $payload = [
        'external_account_id' => '184789120940244992',
        'username' => 'danielhe4rt',
    ];

    $response = $this
        ->actingAsAdmin()
        ->postJson(route('providers.store', ['provider' => $provider]), $payload, [
            'X-He4rt-Provider' => IdentityProvider::Discord->value,
            'X-He4rt-Provider-Id' => '123',
        ]);

    $response->assertStatus(
        Response::HTTP_CREATED
    );

    $this->assertDatabaseHas('users', [
        'username' => $payload['username'],
    ]);

    $this->assertDatabaseHas('external_identities', [
        'provider' => $provider,
        'external_account_id' => $payload['external_account_id'],
    ]);

    $this->assertDatabaseHas('characters', [
        'user_id' => $response['model_id'],
    ]);
});

test('should not create account with a registered provider', function (): void {
    $provider = ExternalIdentity::factory()->create([
        'provider' => 'discord',
        'external_account_id' => '123',
    ]);

    $payload = [
        'external_account_id' => $provider->external_account_id,
        'username' => 'danielhe4rt',
    ];

    $response = $this
        ->actingAsAdmin()
        ->postJson(route('providers.store', ['provider' => $provider->provider]), $payload, [
            'X-He4rt-Provider' => IdentityProvider::Discord->value,
            'X-He4rt-Provider-Id' => '123',
        ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseMissing('users', [
        'username' => $payload['username'],
    ]);
});
