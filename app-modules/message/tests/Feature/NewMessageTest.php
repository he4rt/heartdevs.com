<?php

declare(strict_types=1);

use He4rt\Character\Models\Character;
use He4rt\Meeting\Models\Meeting;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Support\Facades\Cache;

test('can create amessage', function (): void {
    Cache::tags(['meetings'])->flush();

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
        ->has(Character::factory(['tenant_id' => $tenant->getKey(), 'experience' => 1]), 'character')
        ->has(Provider::factory(['tenant_id' => $tenant->getKey()]), 'providers')
        ->create();

    $provider = $user->providers[0];
    $payload = [
        'provider' => $provider->provider->value,
        'provider_id' => $provider->provider_id,
        'provider_message_id' => '12312312',
        'channel_id' => '312321',
        'content' => '321312',
        'sent_at' => now()->toDateTimeString(),
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('messages.create', ['provider' => $provider->provider->value]), $payload, [
            'X-He4rt-Provider' => 'discord',
            'X-He4rt-Provider-Id' => '123',
        ])
        ->assertNoContent();

    $this->assertDatabaseMissing('characters', [
        'user_id' => $user->getKey(),
        'experience' => 1,
    ]);
});

test('can create amessage with level zero', function (): void {
    Cache::tags(['meetings'])->flush();
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
        ->has(Character::factory(['tenant_id' => $tenant->getKey(), 'experience' => 0]), 'character')
        ->has(Provider::factory(['tenant_id' => $tenant->getKey()]), 'providers')
        ->create();
    $provider = $user->providers[0];
    $payload = [
        'provider' => $provider->provider->value,
        'provider_id' => $provider->provider_id,
        'provider_message_id' => '12312312',
        'channel_id' => '312321',
        'content' => '321312',
        'sent_at' => now()->toDateTimeString(),
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('messages.create', ['provider' => $provider->provider->value]), $payload)
        ->assertNoContent();

    $this->assertDatabaseMissing('characters', [
        'user_id' => $user->getKey(),
        'experience' => 1,
    ]);
});

test('can create amessage and receive ameeting check', function (): void {
    Cache::tags(['meetings'])->flush();

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
        ->has(Character::factory(['tenant_id' => $tenant->getKey(), 'experience' => 1]), 'character')
        ->has(Provider::factory(['tenant_id' => $tenant->getKey()]), 'providers')
        ->create();

    $meeting = Meeting::factory()
        ->unfinished()
        ->create();

    Cache::tags(['meetings'])->put('current-meeting', $meeting->id);

    $provider = $user->providers[0];
    $payload = [
        'provider' => $provider->provider->value,
        'provider_id' => $provider->provider_id,
        'provider_message_id' => '12312312',
        'channel_id' => '312321',
        'content' => '321312',
        'sent_at' => now()->toDateTimeString(),
    ];

    $this
        ->actingAsAdmin()
        ->postJson(route('messages.create', ['provider' => $provider->provider]), $payload)
        ->assertNoContent();

    $this->assertDatabaseMissing('characters', [
        'user_id' => $user->getKey(),
        'experience' => 1,
    ]);

    $this->assertDatabaseHas('meeting_participants', [
        'meeting_id' => $meeting->id,
        'user_id' => $user->id,
    ]);
    $userAttendedCacheKey = sprintf('meeting-%s-attended', $user->id);
    expect(Cache::tags(['meetings'])->has($userAttendedCacheKey))->toBeTrue();
    Cache::tags(['meetings'])->flush();

    expect(Cache::tags(['meetings'])->has($userAttendedCacheKey))->toBeFalse();
});
