<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\FindOrCreateUserByProvider;
use He4rt\Identity\Auth\DTOs\OAuthAccessDTO;
use He4rt\Identity\Auth\DTOs\OAuthUserDTO;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

function makeOAuthUser(
    string $providerId = '12345',
    IdentityProvider $provider = IdentityProvider::GitHub,
    string $username = 'testuser',
    string $name = 'Test User',
    ?string $email = 'test@example.com',
): OAuthUserDTO {
    $credentials = new class('token', 'refresh', 3_600) extends OAuthAccessDTO
    {
        public static function make(array $payload): self
        {
            return new self('token', 'refresh', 3_600);
        }
    };

    return new class($credentials, $providerId, $provider, $username, $name, $email, avatarUrl: null) extends OAuthUserDTO
    {
        public static function make(OAuthAccessDTO $credentials, array $payload): self
        {
            return new self($credentials, '', IdentityProvider::GitHub, '', '', null, null);
        }
    };
}

test('finds existing user by external identity', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'external_account_id' => '12345',
    ]);

    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: '12345', provider: IdentityProvider::GitHub),
    );

    expect($result->id)->toBe($user->id);
});

test('finds existing user by email', function (): void {
    $user = User::factory()->create(['email' => 'daniel@example.com']);

    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', email: 'daniel@example.com'),
    );

    expect($result->id)->toBe($user->id);
});

test('does not search by email when email is null', function (): void {
    User::factory()->create(['email' => null, 'username' => 'someone']);

    $userCountBefore = User::query()->count();

    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'newuser', email: null),
    );

    expect($result->username)->toBe('newuser');
    expect(User::query()->count())->toBe($userCountBefore + 1);
});

test('creates new user when no match found', function (): void {
    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: 'fresh-id', username: 'freshuser', name: 'Fresh User', email: 'fresh@example.com'),
    );

    expect($result->username)->toBe('freshuser')
        ->and($result->email)->toBe('fresh@example.com')
        ->and($result->name)->toBe('Fresh User');
});

test('creates user with sequential suffix when username collides', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);

    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'danielhe4rt', email: 'new@example.com'),
    );

    expect($result->username)->toBe('danielhe4rt-2')
        ->and($result->email)->toBe('new@example.com');
});

test('increments suffix when previous suffixed usernames exist', function (): void {
    User::factory()->create(['username' => 'danielhe4rt']);
    User::factory()->create(['username' => 'danielhe4rt-2']);
    User::factory()->create(['username' => 'danielhe4rt-3']);

    $action = resolve(FindOrCreateUserByProvider::class);
    $result = $action->execute(
        makeOAuthUser(providerId: 'new-id', username: 'danielhe4rt', email: 'new@example.com'),
    );

    expect($result->username)->toBe('danielhe4rt-4');
});
