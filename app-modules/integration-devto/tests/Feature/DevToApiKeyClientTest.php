<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;
use He4rt\IntegrationDevTo\ApiKey\DevToApiKeyClient;
use Illuminate\Support\Facades\Http;

test('authenticates with the api-key header', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => 12_345,
            'username' => 'danielhe4rt',
            'name' => 'Daniel',
            'email' => 'daniel@example.com',
            'profile_image' => 'https://example.com/avatar.png',
        ]),
    ]);

    $user = resolve(DevToApiKeyClient::class)->getAuthenticatedUser('secret-key');

    Http::assertSent(
        fn ($request): bool => $request->hasHeader('api-key', 'secret-key')
            && !$request->hasHeader('Authorization')
    );

    expect($user->providerId)->toBe('12345')
        ->and($user->provider)->toBe(IdentityProvider::DevTo)
        ->and($user->username)->toBe('danielhe4rt')
        ->and($user->name)->toBe('Daniel')
        ->and($user->email)->toBe('daniel@example.com')
        ->and($user->avatarUrl)->toBe('https://example.com/avatar.png');
});

test('falls back to the username when the profile has no name or email', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response([
            'id' => 999,
            'username' => 'ghost',
        ]),
    ]);

    $user = resolve(DevToApiKeyClient::class)->getAuthenticatedUser('secret-key');

    expect($user->name)->toBe('ghost')
        ->and($user->email)->toBeNull()
        ->and($user->avatarUrl)->toBeNull()
        ->and($user->toMetadata())->toBe(['username' => 'ghost']);
});

test('rejects an invalid key', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    expect(fn () => resolve(DevToApiKeyClient::class)->getAuthenticatedUser('nope'))
        ->toThrow(InvalidApiKeyException::class);
});

test('treats an upstream failure as a rejected key instead of a valid profile', function (): void {
    Http::fake([
        'dev.to/api/users/me' => Http::response('boom', 500),
    ]);

    try {
        resolve(DevToApiKeyClient::class)->getAuthenticatedUser('any-key');
    } catch (InvalidApiKeyException $invalidApiKeyException) {
        expect($invalidApiKeyException->status)->toBe(500)
            ->and($invalidApiKeyException->provider)->toBe(IdentityProvider::DevTo);

        return;
    }

    $this->fail('Expected InvalidApiKeyException was not thrown.');
});
