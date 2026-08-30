<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Actions\FindConnectedUser;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

it('returns the linked user for an actively connected identity', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()
        ->morphFor(User::class)
        ->create([
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
        ]);

    $result = (new FindConnectedUser)->execute(IdentityProvider::Discord, '123456789');

    expect($result?->id)->toBe($user->id);
});

it('returns null for an identity created by the ETL, without real credentials', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()
        ->morphFor(User::class)
        ->create([
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
            'credentials' => ClientAccessManager::make(),
        ]);

    $result = (new FindConnectedUser)->execute(IdentityProvider::Discord, '123456789');

    expect($result)->toBeNull();
});

it('returns null when no identity matches the provider and external account id', function (): void {
    $result = (new FindConnectedUser)->execute(IdentityProvider::Discord, 'nonexistent');

    expect($result)->toBeNull();
});
