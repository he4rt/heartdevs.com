<?php

declare(strict_types=1);

use He4rt\BotDiscord\Actions\UserCharacterResolver;
use He4rt\Character\Models\Character;
use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

it('creates a new user, provider and character when no provider entry exists for the given providerId', function (): void {
    $resolver = app(UserCharacterResolver::class);

    $provider = ProviderEnum::Discord;
    $providerId = '123456789';
    $username = 'TestUser';

    $tenant = Tenant::factory()->create();
    $tenantId = $tenant->id;

    $resolved = $resolver->resolve(
        provider: $provider,
        providerId: $providerId,
        username: $username,
        tenantId: $tenantId
    );

    expect($resolved->isNewUser)->toBeTrue()
        ->and($resolved->provider)->not()->toBeNull()
        ->and($resolved->character)->not()->toBeNull()
        ->and($resolved->character->experience)->toBe(0)
        ->and($resolved->provider->provider)->toBe($provider)
        ->and($resolved->provider->provider_id)->toBe($providerId)
        ->and($resolved->provider->tenant_id)->toBe($tenantId)
        ->and($resolved->character->tenant_id)->toBe($tenantId)
        ->and(User::query()->count())->toBe(2) // Because Tenant factory creates an owner user
        ->and(Provider::query()->count())->toBe(1)
        ->and(Character::query()->count())->toBe(1);
});

it('returns the existing user, provider and character when the provider entry already exists', function (): void {
    $provider = ProviderEnum::Discord;
    $providerId = '9999';

    $user = User::factory()->create();

    $tenant = Tenant::factory()->create([
        'owner_id' => $user->id,
    ]);
    $tenantId = $tenant->id;

    $character = $user->character()->create([
        'tenant_id' => $tenantId,
    ]);

    $providerEntity = $user->providers()->create([
        'tenant_id' => $tenantId,
        'provider' => $provider,
        'provider_id' => $providerId,
    ]);

    $resolver = app(UserCharacterResolver::class);

    $resolved = $resolver->resolve(
        provider: $provider,
        providerId: $providerId,
        username: 'IgnoredHere',
        tenantId: $tenantId
    );

    expect($resolved->isNewUser)->toBeFalse()
        ->and($resolved->provider->id)->toBe($providerEntity->id)
        ->and($resolved->character->id)->toBe($character->id)
        ->and($resolved->character->experience)->toBe(0)
        ->and(User::query()->count())->toBe(1)
        ->and(Provider::query()->count())->toBe(1)
        ->and(Character::query()->count())->toBe(1);

});
