<?php

declare(strict_types=1);

use He4rt\Character\Models\Character;
use He4rt\Character\Models\PastSeason;
use He4rt\Message\Models\Message;
use He4rt\Provider\Models\Provider;
use He4rt\User\Models\Address;
use He4rt\User\Models\Information;
use He4rt\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

test('success', function (): void {
    $user = User::factory()
        ->has(Character::factory()->has(PastSeason::factory()), 'character')
        ->has(Address::factory(), 'address')
        ->has(Information::factory(), 'information')
        ->has(Provider::factory()->has(Message::factory()->count(2)))
        ->create();

    $payload = [
        'info' => [
            'name' => 'daniel corazon',
            'nickname' => 'danielhe4rt#0001',
            'linkedin_url' => 'https://linkedin.com/in/danielheart',
            'github_url' => 'https://github.com/danielhe4rt',
            'birthdate' => '1999-08-03',
            'about' => 'definitely a developer',
        ],
    ];

    $response = $this
        ->actingAsAdmin()
        ->putJson(route('users.profile.update', ['value' => $user->username]), $payload);

    $response->assertStatus(Response::HTTP_OK);

    $this->assertDatabaseHas('user_information', $payload['info']);
})->skip();

test('success with one field', function (): void {
    $user = User::factory()
        ->has(Character::factory()->has(PastSeason::factory()), 'character')
        ->has(Address::factory(), 'address')
        ->has(Information::factory(), 'information')
        ->has(Provider::factory()->has(Message::factory()->count(2)))
        ->create();

    $payload = [
        'info' => [
            'github_url' => 'https://github.com/danielhe4rt',
        ],
    ];
    $userExpected = $user->information
        ->only(['nickname', 'linkedin_url']);

    $response = $this
        ->actingAsAdmin()
        ->putJson(route('users.profile.update', ['value' => $user->username]), $payload);

    $userExpected['github_url'] = $payload['info']['github_url'];

    $response->assertStatus(Response::HTTP_OK);

    $this->assertDatabaseHas('user_information', $userExpected);
})->skip();
