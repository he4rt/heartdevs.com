<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Character\Models\PastSeason;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\Address;
use He4rt\Identity\User\Models\Information;
use He4rt\Identity\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('can find profile with username', function (): void {
    $user = User::factory()
        ->has(Character::factory()->has(PastSeason::factory()), 'character')
        ->has(Address::factory(), 'address')
        ->has(Information::factory(), 'information')
        ->has(ExternalIdentity::factory()->has(Message::factory()->count(2), 'messages'), 'providers')
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
        ->has(ExternalIdentity::factory()->has(Message::factory()->count(2), 'messages'), 'providers')
        ->create();
    $badge = Badge::factory()->create();

    $character = $user->character;
    $tenant = Tenant::factory()->create();
    $character->badges()->attach($badge->id, ['claimed_at' => now(), 'tenant_id' => $tenant->getKey()]);

    $this
        ->actingAsAdmin()
        ->getJson(route('users.profile', ['value' => $user->providers[0]->external_account_id]))
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
