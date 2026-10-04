<?php

declare(strict_types=1);

use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\Identity\Auth\Enums\OAuthIntent;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\OAuth\TwitchOAuthClient;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;

beforeEach(function (): void {
    config()->set('services.twitch.client_id', 'fake-client-id');
    config()->set('services.twitch.client_secret', 'fake-secret');
    config()->set('services.twitch.scopes', [
        'app' => 'user:read:email',
        'streamer' => 'user:read:email moderator:read:followers channel:read:subscriptions bits:read',
        'admin' => 'user:read:email moderator:read:followers channel:bot',
    ]);
});

test('o conjunto de escopos depende de quem conecta e de onde', function (string $panel, ?string $userType, string $expected): void {
    $user = match ($userType) {
        'membro' => User::factory()->create(),
        'streamer' => User::factory()->streamer()->create(),
        'super-admin' => User::factory()->superAdmin()->create(),
        default => null,
    };

    expect(TwitchScopes::requestedFor($panel, $user))
        ->toBe(explode(' ', config()->string('services.twitch.scopes.'.$expected)));
})->with([
    'visitante fazendo login no /app' => ['app', null, 'app'],
    'membro conectando no /app' => ['app', 'membro', 'app'],
    'streamer conectando no /app' => ['app', 'streamer', 'streamer'],
    'super admin conectando no /app' => ['app', 'super-admin', 'streamer'],
    'streamer conectando no /admin' => ['admin', 'streamer', 'admin'],
    'painel sem conjunto próprio' => ['event', null, 'app'],
]);

test('o redirect pede à Twitch os escopos de streamer para quem tem a role', function (): void {
    $this->actingAs(User::factory()->streamer()->create());

    $redirectUrl = resolve(TwitchOAuthClient::class)->redirectUrl(new OAuthStateDTO(
        intent: OAuthIntent::Link,
        provider: IdentityProvider::Twitch,
        panel: 'app',
    ));

    parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $query);

    expect($query['scope'])->toBe('user:read:email moderator:read:followers channel:read:subscriptions bits:read');
});

test('o card mostra os mesmos escopos que o redirect pede', function (): void {
    $streamer = User::factory()->streamer()->create();

    expect(IdentityProvider::Twitch->getScopes('app', $streamer))
        ->toBe(TwitchScopes::requestedFor('app', $streamer));
});
