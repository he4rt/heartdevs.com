<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Exceptions\ExternalIdentityException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('finds identity by provider and providerId', function (): void {
    $user = User::factory()->create();

    $identity = ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-999',
    ]);

    $action = new FindExternalIdentity();
    $result = $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'discord-999',
    );

    expect($result->id)->toBe($identity->id)
        ->and($result->model_id)->toBe($user->id);
});

test('caches the lookup result', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-cache-test',
    ]);

    Cache::flush();

    $action = new FindExternalIdentity();
    $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'discord-cache-test',
    );

    $expectedKey = sprintf('provider-%s-%s', IdentityProvider::Discord->value, 'discord-cache-test');
    expect(Cache::has($expectedKey))->toBeTrue();
});

test('throws exception when identity not found', function (): void {
    $action = new FindExternalIdentity();

    $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'nonexistent-id',
    );
})->throws(ExternalIdentityException::class);
