<?php

declare(strict_types=1);

use He4rt\Character\Enums\VoiceStatesEnum;
use He4rt\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

test('can create voice message', function (): void {
    config(['he4rt.season.id' => 2]);

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            ExternalIdentity::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => IdentityProvider::Discord,
                'provider_id' => '123',
            ])->create();
        })
        ->create();

    $user = User::factory()
        ->has(Character::factory(['tenant_id' => $tenant->getKey(), 'experience' => 1]), 'character')
        ->has(ExternalIdentity::factory(['tenant_id' => $tenant->getKey()]), 'providers')
        ->create();

    $provider = $user->providers[0];
    $payload = [
        'provider' => $provider->provider->value,
        'provider_id' => $provider->provider_id,
        'state' => VoiceStatesEnum::Muted->value,
        'channel_name' => 'Estudando',
    ];

    $this->actingAsAdmin()
        ->post(route('voices.create', $provider->provider->value), $payload)
        ->assertNoContent();

    $this->assertDatabaseMissing('characters', [
        'user_id' => $user->getKey(),
        'experience' => 1,
    ]);

    $this->assertDatabaseHas('voice_messages', [
        'state' => $payload['state'],
        'channel_name' => $payload['channel_name'],
    ]);
});
