<?php

declare(strict_types=1);

use He4rt\Authentication\Actions\AuthenticateAction;
use He4rt\Authentication\Enums\OAuthProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
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

    $action = app(AuthenticateAction::class);

    $action->withOAuth(tenantSlug: $tenant->slug, oauthProvider: OAuthProviderEnum::Discord, code: 'dummy_code');

    $user = Auth::user();
    expect($user)->not->toBeNull();
    expect($user)->toBeInstanceOf(User::class);

    // Assert provider created and linked
    $provider = Provider::query()->first();
    expect($provider)->not->toBeNull();
    expect($provider->provider->value)->toBe('discord');
    expect($provider->provider_id)->toBe('1234567890');
    expect($provider->user->is($user))->toBeTrue();

    // Token created for new provider
    $provider->refresh();
    expect($provider->tokens()->count())->toBe(1);
    $token = $provider->tokens()->first();
    expect($token->access_token)->toBe('discord_access_token_123');
    expect($token->refresh_token)->toBe('discord_refresh_token_456');
});

it('authenticates an existing provider without duplicating records', function (): void {
    setDiscordConfig();
    fakeDiscordOAuthResponses(['user' => ['id' => '777777', 'email' => 'existing@example.test']]);

    $tenant = Tenant::factory()->create([
        'slug' => 'he4rt',
    ]);

    $existingUser = User::factory()->create();
    $existingProvider = Provider::factory()->create([
        'tenant_id' => $tenant->getKey(),
        'model_type' => User::class,
        'model_id' => $existingUser->getKey(),
        'provider' => 'discord',
        'provider_id' => '777777',
        'email' => 'existing@example.test',
    ]);

    $initialProviders = Provider::query()->count();

    $action = app(AuthenticateAction::class);
    $action->withOAuth(tenantSlug: $tenant->slug, oauthProvider: OAuthProviderEnum::Discord, code: 'dummy_code');

    // Should log in the same existing user
    $user = Auth::user();
    expect($user)->not->toBeNull();
    expect($user->getKey())->toBe($existingUser->getKey());

    // No duplicate providers
    expect(Provider::query()->count())->toBe($initialProviders);

    // By current implementation, tokens are only created on new registrations
    // Ensure no new tokens were created for the existing provider
    $existingProvider->refresh();
    expect($existingProvider->tokens()->count())->toBe(0);
});
