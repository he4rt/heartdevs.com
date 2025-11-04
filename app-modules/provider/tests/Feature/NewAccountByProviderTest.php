<?php

declare(strict_types=1);

use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use Symfony\Component\HttpFoundation\Response;

test('can create account by provider', function (): void {
    $provider = 'discord';
    $payload = [
        'provider_id' => '184789120940244992',
        'username' => 'danielhe4rt',
    ];

    $response = $this
        ->actingAsAdmin()
        ->postJson(route('providers.store', ['provider' => $provider]), $payload, [
            'X-He4rt-Provider' => ProviderEnum::Discord->value,
            'X-He4rt-Provider-Id' => '123',
        ]);

    $response->assertStatus(
        Response::HTTP_CREATED
    );

    $this->assertDatabaseHas('users', [
        'username' => $payload['username'],
    ]);

    $this->assertDatabaseHas('providers', [
        'provider' => $provider,
        'provider_id' => $payload['provider_id'],
    ]);

    $this->assertDatabaseHas('characters', [
        'user_id' => $response['modelId'],
    ]);
});

test('should not create account with a registered provider', function (): void {
    $provider = Provider::factory()->create([
        'provider' => 'discord',
        'provider_id' => '123',
    ]);

    $payload = [
        'provider_id' => $provider->provider_id,
        'username' => 'danielhe4rt',
    ];

    $response = $this
        ->actingAsAdmin()
        ->postJson(route('providers.store', ['provider' => $provider->provider]), $payload, [
            'X-He4rt-Provider' => ProviderEnum::Discord->value,
            'X-He4rt-Provider-Id' => '123',
        ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseMissing('users', [
        'username' => $payload['username'],
    ]);
});
