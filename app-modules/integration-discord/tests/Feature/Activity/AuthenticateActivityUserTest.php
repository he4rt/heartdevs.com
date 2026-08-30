<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\Identity\ExternalIdentity\Actions\FindConnectedUser;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationDiscord\Activity\Actions\AuthenticateActivityUser;
use He4rt\IntegrationDiscord\Activity\DTOs\ActivityAuthResult;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function fakeDiscordActivityMockClient(array $userPayload): MockClient
{
    return new MockClient([
        ExchangeCodeForToken::class => MockResponse::make([
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_in' => 604_800,
        ]),
        GetCurrentUser::class => MockResponse::make($userPayload),
    ]);
}

it('returns the linked user and the access token when the discord account is connected', function (): void {
    $user = User::factory()->create();

    ExternalIdentity::factory()
        ->morphFor(User::class)
        ->create([
            'model_id' => $user->id,
            'provider' => IdentityProvider::Discord,
            'external_account_id' => '123456789',
        ]);

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient(fakeDiscordActivityMockClient(['id' => '123456789', 'username' => 'testuser']));

    $result = new AuthenticateActivityUser($connector, new FindConnectedUser)
        ->execute('some-code');

    expect($result)
        ->toBeInstanceOf(ActivityAuthResult::class)
        ->and($result->accessToken)->toBe('test-access-token')
        ->and($result->user?->id)->toBe($user->id);
});

it('returns a null user with the access token still filled when the discord account has no linked user', function (): void {
    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient(fakeDiscordActivityMockClient(['id' => 'unlinked-discord-id', 'username' => 'ghost']));

    $result = new AuthenticateActivityUser($connector, new FindConnectedUser)
        ->execute('some-code');

    expect($result->user)->toBeNull()
        ->and($result->accessToken)->toBe('test-access-token');
});

it('throws when the token exchange fails', function (): void {
    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $connector->withMockClient(new MockClient([
        ExchangeCodeForToken::class => MockResponse::make(['error' => 'invalid_grant'], 400),
    ]));

    new AuthenticateActivityUser($connector, new FindConnectedUser)
        ->execute('bad-code');
})->throws(OAuthFlowException::class);
