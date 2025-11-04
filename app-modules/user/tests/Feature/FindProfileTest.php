<?php

declare(strict_types=1);

use He4rt\Badge\Models\Badge;
use He4rt\Character\Models\Character;
use He4rt\Character\Models\PastSeason;
use He4rt\Message\Models\Message;
use He4rt\Provider\Models\Provider;
use He4rt\User\Models\Address;
use He4rt\User\Models\Information;
use He4rt\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('can find profile with username', function (): void {
    $user = User::factory()
        ->has(Character::factory()->has(PastSeason::factory()), 'character')
        ->has(Address::factory(), 'address')
        ->has(Information::factory(), 'information')
        ->has(Provider::factory()->has(Message::factory()->count(2)))
        ->create();

    //        $character->badges()->attach($badge->id, ['claimed_at' => now()]);
    $this
        ->actingAsAdmin()
        ->getJson(route('users.profile', ['value' => $user->username]))
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'id',
            'username',
            'character' => [
                'user_id',
                'reputation',
                'level',
                'experience',
                'daily_bonus_claimed_at',
            ],
            'connectedProviders' => [
                0 => [
                    'provider',
                    'messages_count',
                ],
            ],
            'badges',
            'address' => [
                'country',
            ],
            'pastSeasons' => [
                0 => [
                    'season_id',
                ],
            ],
        ]);
});

test('can find profile with provider id', function (): void {
    $user = User::factory()
        ->has(Character::factory()->has(PastSeason::factory()), 'character')
        ->has(Address::factory(), 'address')
        ->has(Information::factory(), 'information')
        ->has(Provider::factory()->has(Message::factory()->count(2)))
        ->create();
    $badge = Badge::factory()->create();

    $character = $user->character;
    $character->badges()->attach($badge->id, ['claimed_at' => now(), 'tenant_id' => 1]);

    $this
        ->actingAsAdmin()
        ->getJson(route('users.profile', ['value' => $user->providers[0]->provider_id]))
        ->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'id',
            'username',
            'character' => [
                'user_id',
                'reputation',
                'level',
                'experience',
                'daily_bonus_claimed_at',
            ],
            'connectedProviders' => [
                0 => [
                    'provider',
                    'messages_count',
                ],
            ],
            'badges',
            'address' => [
                'country',
            ],
            'pastSeasons' => [
                0 => [
                    'season_id',
                ],
            ],
        ]);
});
