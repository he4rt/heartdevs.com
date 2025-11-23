<?php

declare(strict_types=1);

use He4rt\Badge\Models\Badge;
use He4rt\Character\Models\Character;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('can claim badge', function (): void {
    $badge = Badge::factory()
        ->create();

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            Provider::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => ProviderEnum::Discord,
                'provider_id' => '123',
            ])->create();
        })
        ->create();

    $user = User::factory()
        ->has(Character::factory(['tenant_id' => $tenant]), 'character')
        ->has(Provider::factory(['tenant_id' => $tenant]), 'providers')
        ->create();

    $provider = $user->providers[0];

    $response = $this
        ->actingAsAdmin()
        ->postJson(route('characters.claimBadge', [
            'provider' => $provider->provider->value,
            'providerId' => $provider->provider_id,
        ]), ['redeem_code' => $badge->redeem_code], [
            'X-He4rt-Provider' => $provider->provider->value,
            'X-He4rt-Provider-Id' => $provider->provider_id,
        ]);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('characters_badges', [
        'tenant_id' => $tenant->getKey(),
        'character_id' => $user->character->id,
        'badge_id' => $badge->id,
    ]);
});
