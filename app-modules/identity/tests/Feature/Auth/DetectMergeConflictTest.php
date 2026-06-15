<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\DetectMergeConflict;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

function makeMergeOAuthUser(
    string $providerId = '204122995579551744',
    IdentityProvider $provider = IdentityProvider::Discord,
): OAuthUserDTO {
    $credentials = new class('token', 'refresh', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('token', 'refresh', 3_600);
        }
    };

    return new class($credentials, $providerId, $provider, 'testuser', 'Test User', 'test@example.com', null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::Discord, '', '', null, null);
        }
    };
}

function makeMergeCredentials(): OAuthAccessDTO
{
    return new class('access-token', 'refresh-token', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('access-token', 'refresh-token', 3_600);
        }
    };
}

test('returns null when no conflicting identity exists', function (): void {
    $currentUser = User::factory()->create();
    $oauthUser = makeMergeOAuthUser(providerId: 'fresh-id');
    $credentials = makeMergeCredentials();

    $action = new DetectMergeConflict();
    $result = $action->execute($currentUser, $oauthUser, $credentials);

    expect($result)->toBeNull();
});

test('returns null when identity belongs to current user', function (): void {
    $tenant = Tenant::factory()->create();
    $currentUser = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $currentUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '204122995579551744',
    ]);

    $oauthUser = makeMergeOAuthUser(providerId: '204122995579551744');
    $credentials = makeMergeCredentials();

    $action = new DetectMergeConflict();
    $result = $action->execute($currentUser, $oauthUser, $credentials);

    expect($result)->toBeNull();
});

test('returns conflict when identity belongs to a different user', function (): void {
    $tenant = Tenant::factory()->create();
    $oldUser = User::factory()->create();
    $currentUser = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $oldUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '204122995579551744',
    ]);

    $oauthUser = makeMergeOAuthUser(providerId: '204122995579551744');
    $credentials = makeMergeCredentials();

    $action = new DetectMergeConflict();
    $result = $action->execute($currentUser, $oauthUser, $credentials);

    expect($result)->not->toBeNull()
        ->and($result->conflictingUserId)->toBe($oldUser->id)
        ->and($result->provider)->toBe(IdentityProvider::Discord);
});

test('detects conflict cross-tenant', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $oldUser = User::factory()->create();
    $currentUser = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenantA->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $oldUser->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '204122995579551744',
    ]);

    $oauthUser = makeMergeOAuthUser(providerId: '204122995579551744');
    $credentials = makeMergeCredentials();

    $action = new DetectMergeConflict();
    $result = $action->execute($currentUser, $oauthUser, $credentials);

    expect($result)->not->toBeNull()
        ->and($result->conflictingUserId)->toBe($oldUser->id);
});

test('ignores tenant-owned identities', function (): void {
    $tenant = Tenant::factory()->create();
    $currentUser = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new Tenant)->getMorphClass(),
        'model_id' => $tenant->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '204122995579551744',
    ]);

    $oauthUser = makeMergeOAuthUser(providerId: '204122995579551744');
    $credentials = makeMergeCredentials();

    $action = new DetectMergeConflict();
    $result = $action->execute($currentUser, $oauthUser, $credentials);

    expect($result)->toBeNull();
});
