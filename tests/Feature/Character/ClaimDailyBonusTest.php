<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Heart\Character\Infrastructure\Models\Character;
use Heart\Provider\Infrastructure\Models\Provider;
use Heart\User\Infrastructure\Models\User;
use Symfony\Component\HttpFoundation\Response;

uses(DatabaseTransactions::class);

test('success', function (): void {
    $user = User::factory()
        ->has(Provider::factory(), 'providers')
        ->has(Character::factory(), 'character')
        ->create();

    $provider = $user->providers[0];
    $routeParams = [
        'provider' => $provider->provider,
        'providerId' => $provider->provider_id,
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
    $user = User::factory()
        ->has(Provider::factory(), 'providers')
        ->has(Character::factory(), 'character')
        ->create();

    $provider = $user->providers[0];
    $routeParams = [
        'provider' => $provider->provider,
        'providerId' => $provider->provider_id,
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('characters.dailyReward', $routeParams))
        ->assertStatus(Response::HTTP_FORBIDDEN);
});
