<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('success', function (): void {

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            ExternalIdentity::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'external_account_id' => '123',
            ])->create();
        })
        ->create();

    $user = User::factory()
        ->has(ExternalIdentity::factory(['tenant_id' => $tenant]), 'providers')
        ->has(Character::factory(['tenant_id' => $tenant]), 'character')
        ->create();

    $provider = $user->providers[0];
    $routeParams = [
        'provider' => $provider->provider,
        'providerId' => $provider->external_account_id,
    ];
    $expected = $user->character->daily_bonus_claimed_at;
    $this->travelTo(now()->addHours(24)->addMinutes(2));
    $this
        ->actingAsAdmin()
        ->postJson(route('characters.dailyReward', $routeParams))
        ->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('characters', [
        'daily_bonus_claimed_at' => $expected,
    ]);
});

test('should not claim before24 hours', function (): void {

    $tenant = Tenant::factory()
        ->afterCreating(function (Tenant $tenant): void {
            ExternalIdentity::factory([
                'tenant_id' => $tenant->getKey(),
                'provider' => 'discord',
                'external_account_id' => '123',
            ])->create();
        })
        ->create();

    $user = User::factory()
        ->has(ExternalIdentity::factory(['tenant_id' => $tenant->getKey()]), 'providers')
        ->has(Character::factory(['tenant_id' => $tenant->getKey()]), 'character')
        ->create();

    $provider = $user->providers[0];
    $routeParams = [
        'provider' => $provider->provider,
        'providerId' => $provider->external_account_id,
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('characters.dailyReward', $routeParams))
        ->assertStatus(Response::HTTP_FORBIDDEN);
});
