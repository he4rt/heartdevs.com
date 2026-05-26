<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\FindOrCreateUserByProvider;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

function makeOAuthUser(
    string $providerId = '12345',
    IdentityProvider $provider = IdentityProvider::GitHub,
    string $username = 'testuser',
    string $name = 'Test User',
    ?string $email = 'test@example.com',
): OAuthUserDTO {
    $credentials = new class('token', 'refresh', 3600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('token', 'refresh', 3600);
        }
    };

    return new class($credentials, $providerId, $provider, $username, $name, $email, null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::GitHub, '', '', null, null);
        }
    };
}

test('finds existing user by external identity cross-tenant', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenantA->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => '12345',
    ]);

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: '12345', provider: IdentityProvider::GitHub),
        $tenantB,
    );

    expect($result->id)->toBe($user->id);
});

test('finds existing user by email', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['email' => 'daniel@example.com']);

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', email: 'daniel@example.com'),
        $tenant,
    );

    expect($result->id)->toBe($user->id);
});

test('does not search by email when email is null', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['email' => null, 'username' => 'someone']);

    $userCountBefore = User::query()->count();

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'newuser', email: null),
        $tenant,
    );

    expect($result->username)->toBe('newuser');
    expect(User::query()->count())->toBe($userCountBefore + 1);
});

test('creates new user when no match found', function (): void {
    $tenant = Tenant::factory()->create();

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'fresh-id', username: 'freshuser', name: 'Fresh User', email: 'fresh@example.com'),
        $tenant,
    );

    expect($result->username)->toBe('freshuser')
        ->and($result->email)->toBe('fresh@example.com')
        ->and($result->name)->toBe('Fresh User');
});

test('creates user with sequential suffix when username collides', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['username' => 'danielhe4rt']);

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'danielhe4rt', email: 'new@example.com'),
        $tenant,
    );

    expect($result->username)->toBe('danielhe4rt-2')
        ->and($result->email)->toBe('new@example.com');
});

test('increments suffix when previous suffixed usernames exist', function (): void {
    $tenant = Tenant::factory()->create();
    User::factory()->create(['username' => 'danielhe4rt']);
    User::factory()->create(['username' => 'danielhe4rt-2']);
    User::factory()->create(['username' => 'danielhe4rt-3']);

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'danielhe4rt', email: 'new@example.com'),
        $tenant,
    );

    expect($result->username)->toBe('danielhe4rt-4');
});

test('attaches user to tenant when not already attached', function (): void {
    $tenant = Tenant::factory()->create();

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'newuser'),
        $tenant,
    );

    expect($result->tenants()->where('tenants.id', $tenant->getKey())->exists())->toBeTrue();
});

test('does not duplicate tenant attachment when already attached', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $user->tenants()->attach($tenant);

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => '12345',
    ]);

    $action = new FindOrCreateUserByProvider();
    $action->execute(
        makeOAuthUser(providerId: '12345', provider: IdentityProvider::GitHub),
        $tenant,
    );

    expect($user->tenants()->count())->toBe(1);
});

test('ignores tenant-owned external identities during lookup', function (): void {
    $tenant = Tenant::factory()->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => (new Tenant)->getMorphClass(),
        'model_id' => $tenant->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => '204122995579551744',
    ]);

    $action = new FindOrCreateUserByProvider();
    $result = $action->execute(
        makeOAuthUser(providerId: '204122995579551744', provider: IdentityProvider::Discord, username: 'newuser'),
        $tenant,
    );

    expect($result->username)->toBe('newuser');
    expect(User::query()->where('username', 'newuser')->exists())->toBeTrue();
});
