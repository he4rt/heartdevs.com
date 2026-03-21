<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Actions\AuthenticateAction;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeDiscordOAuthResponses(array $overrides = []): void
{
    $tokenPayload = array_merge([
        'access_token' => 'discord_access_token_123',
        'refresh_token' => 'discord_refresh_token_456',
        'expires_in' => 3600,
        'token_type' => 'Bearer',
        'scope' => 'identify email',
    ], $overrides['token'] ?? []);

    $userPayload = array_merge([
        'id' => '1234567890',
        'username' => 'discordUser',
        'global_name' => 'Discord User',
        'email' => 'discord.user@example.test',
        'avatar' => 'avatarhash',
    ], $overrides['user'] ?? []);

    Http::fake([
        'https://discord.com/api/oauth2/token' => Http::response($tokenPayload, 200),
        'https://discord.com/api/v10/users/@me' => Http::response($userPayload, 200),
    ]);
}

function setDiscordConfig(): void
{
    Config::set('services.discord.client_id', 'test_client_id');
    Config::set('services.discord.client_secret', 'test_client_secret');
    Config::set('services.discord.redirect_uri', 'https://example.test/oauth/discord/callback');
    Config::set('services.discord.scopes', 'identify email');
}

it('authenticates a new user via Discord and persists provider + token', function (): void {
    setDiscordConfig();
    fakeDiscordOAuthResponses();

    $tenant = Tenant::factory()->create([
        'slug' => 'he4rt',
    ]);

    $action = resolve(AuthenticateAction::class);

    $action->withOAuth(state: new OAuthStateDTO('admin', 'he4rt'), oauthProvider: IdentityProvider::Discord, code: 'dummy_code');

    $user = Auth::user();
    expect($user)->not->toBeNull();
    expect($user)->toBeInstanceOf(User::class);

    // Assert provider created and linked
    $provider = ExternalIdentity::query()->first();
    expect($provider)->not->toBeNull();
    expect($provider->provider->value)->toBe('discord');
    expect($provider->external_account_id)->toBe('1234567890');
    expect($provider->user->is($user))->toBeTrue();

    // Credentials stored inline (no more separate tokens table)
    $provider->refresh();
    expect($provider->credentials)->toBeInstanceOf(ClientAccessManager::class);
    expect($provider->credentials->getAccessToken())->toBe('discord_access_token_123');
    expect($provider->credentials->getRefreshToken())->toBe('discord_refresh_token_456');
});

it('authenticates an existing provider without duplicating records', function (): void {
    setDiscordConfig();
    fakeDiscordOAuthResponses(['user' => ['id' => '777777', 'email' => 'existing@example.test']]);

    $tenant = Tenant::factory()->create([
        'slug' => 'he4rt',
    ]);

    $existingUser = User::factory()->create();
    $existingProvider = ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $existingUser->getKey(),
        'provider' => 'discord',
        'external_account_id' => '777777',
        'metadata' => ['email' => 'existing@example.test'],
    ]);

    $initialProviders = ExternalIdentity::query()->count();

    $action = resolve(AuthenticateAction::class);
    $action->withOAuth(state: new OAuthStateDTO('123', 'he4rt'), oauthProvider: IdentityProvider::Discord, code: 'dummy_code');

    // Should log in the same existing user
    $user = Auth::user();
    expect($user)->not->toBeNull();
    expect($user->getKey())->toBe($existingUser->getKey());

    // No duplicate providers
    expect(ExternalIdentity::query()->count())->toBe($initialProviders);

    // Credentials should be updated for existing provider (updateOrCreate)
    $existingProvider->refresh();
    expect($existingProvider->credentials)->toBeInstanceOf(ClientAccessManager::class);
});
