<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function mockDiscordConnectorInApp(string $discordUserId): void
{
    // Client/secret literais bastam — não precisa resolver o singleton real via config.
    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient(new MockClient([
        ExchangeCodeForToken::class => MockResponse::make([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 604_800,
        ]),
        GetCurrentUser::class => MockResponse::make(['id' => $discordUserId, 'username' => 'testuser']),
    ]));

    app()->instance(DiscordOAuthConnector::class, $connector);
}

it('logs the user in and reports linked when the discord account is connected', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()
        ->morphFor(User::class)
        ->create([
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
        ]);

    mockDiscordConnectorInApp('123456789');

    $response = $this->postJson('/discord-activity/auth', ['code' => 'some-code'])
        ->assertSuccessful()
        ->assertJson(['linked' => true, 'access_token' => 'test-access-token']);

    $this->assertAuthenticatedAs($user);

    // Essa rota não tem `frame_id` — sem o PrepareDiscordActivityContext detectar o
    // contexto Discord pelo nome da rota, o cookie sairia SameSite=Lax.
    $sessionCookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === config()->string('session.cookie'));

    expect($sessionCookie)->not->toBeNull()
        ->and($sessionCookie->getSameSite())->toBe('none')
        ->and($sessionCookie->isSecure())->toBeTrue();
});

it('reports not linked without authenticating when the discord account has no linked user', function (): void {
    mockDiscordConnectorInApp('unlinked-discord-id');

    $this->postJson('/discord-activity/auth', ['code' => 'some-code'])
        ->assertSuccessful()
        ->assertJson(['linked' => false, 'access_token' => 'test-access-token']);

    $this->assertGuest();
});

it('validates that code is required', function (): void {
    // O container injeta AuthenticateActivityUser antes da validação rodar, então
    // o connector precisa estar mockado mesmo aqui.
    mockDiscordConnectorInApp('n/a');

    $this->postJson('/discord-activity/auth', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});
