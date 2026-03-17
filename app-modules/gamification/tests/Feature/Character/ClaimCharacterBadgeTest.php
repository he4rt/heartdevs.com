<?php

declare(strict_types=1);

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('can claim badge', function (): void {
    $badge = Badge::factory()
        ->create();

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
        ->has(Character::factory(['tenant_id' => $tenant]), 'character')
        ->has(ExternalIdentity::factory(['tenant_id' => $tenant]), 'providers')
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
