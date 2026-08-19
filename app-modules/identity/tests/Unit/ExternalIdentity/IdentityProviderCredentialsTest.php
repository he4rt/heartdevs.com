<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\IntegrationDevTo\ApiKey\DevToApiKeyClient;

test('devto authenticates via api key', function (): void {
    expect(IdentityProvider::DevTo->getCredentialsType())->toBe(CredentialsType::ApiKey)
        ->and(IdentityProvider::DevTo->getApiKeyClient())->toBeInstanceOf(DevToApiKeyClient::class);
});

test('every other provider defaults to oauth2', function (IdentityProvider $provider): void {
    expect($provider->getCredentialsType())->toBe(CredentialsType::OAuth2)
        ->and($provider->getApiKeyClient())->toBeNull();
})->with([
    IdentityProvider::GitHub,
    IdentityProvider::Discord,
    IdentityProvider::Twitch,
    IdentityProvider::Spotify,
]);

test('supported providers are grouped by credentials type in enum order', function (): void {
    $grouped = IdentityProvider::supportedProvidersByCredentialsType();

    expect(array_keys($grouped))->toBe([
        CredentialsType::OAuth2->value,
        CredentialsType::ApiKey->value,
    ])
        ->and($grouped[CredentialsType::OAuth2->value])->toBe([
            IdentityProvider::GitHub,
            IdentityProvider::Discord,
            IdentityProvider::Twitch,
        ])
        ->and($grouped[CredentialsType::ApiKey->value])->toBe([
            IdentityProvider::DevTo,
        ]);
});

test('a credentials type with no supported provider is omitted instead of rendering an empty group', function (): void {
    expect(IdentityProvider::supportedProvidersByCredentialsType())
        ->not->toHaveKey(CredentialsType::Basic->value);
});

test('an api key provider exposes no oauth scopes', function (): void {
    config(['services.devto.scopes' => 'public']);

    expect(IdentityProvider::DevTo->getScopes())->toBeEmpty();
});

test('devto is offered in the connection hub', function (): void {
    expect(IdentityProvider::supportedProviders())->toContain(IdentityProvider::DevTo);
});
