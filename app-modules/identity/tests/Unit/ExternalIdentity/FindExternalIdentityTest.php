<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Exceptions\ExternalIdentityException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('finds identity when tenantId is passed explicitly', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $identity = ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-999',
    ]);

    $action = new FindExternalIdentity();
    $result = $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'discord-999',
        tenantId: (string) $tenant->id,
    );

    expect($result->id)->toBe($identity->id)
        ->and($result->model_id)->toBe($user->id);
});

test('cache key includes tenantId when provided', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
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
        tenantId: (string) $tenant->id,
    );

    $expectedKey = sprintf('provider-%s-%s-%s', IdentityProvider::Discord->value, 'discord-cache-test', $tenant->id);
    expect(Cache::has($expectedKey))->toBeTrue();
});

test('cache key excludes tenantId when null', function (): void {
    Cache::flush();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-no-tenant',
    ]);

    // Simulate request with tenant_id
    request()->merge(['tenant_id' => (string) $tenant->id]);

    $action = new FindExternalIdentity();
    $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'discord-no-tenant',
    );

    $expectedKey = sprintf('provider-%s-%s', IdentityProvider::Discord->value, 'discord-no-tenant');
    expect(Cache::has($expectedKey))->toBeTrue();
});

test('null tenantId falls back to request input', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-fallback',
    ]);

    request()->merge(['tenant_id' => (string) $tenant->id]);

    $action = new FindExternalIdentity();
    $result = $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'discord-fallback',
    );

    expect($result->model_id)->toBe($user->id);
});

test('throws exception when identity not found', function (): void {
    $action = new FindExternalIdentity();

    $action->handle(
        provider: IdentityProvider::Discord->value,
        providerId: 'nonexistent-id',
        tenantId: '1',
    );
})->throws(ExternalIdentityException::class);
