<?php

declare(strict_types=1);

use He4rt\Identity\Auth\Exceptions\OAuthFlowException;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\GetAppAccessToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function makeAppTokenService(MockClient $mock): TwitchAppTokenService
{
    $connector = new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret');
    $connector->withMockClient($mock);

    return new TwitchAppTokenService($connector);
}

beforeEach(function (): void {
    Cache::flush();
});

test('retorna e cacheia o app token quando a twitch responde com sucesso', function (): void {
    $service = makeAppTokenService(new MockClient([
        GetAppAccessToken::class => MockResponse::make([
            'access_token' => 'app-token-123',
            'expires_in' => 5_000,
            'token_type' => 'bearer',
        ]),
    ]));

    expect($service->getToken())->toBe('app-token-123');
    expect(Cache::get('twitch_app_access_token'))->toBe('app-token-123');
});

test('lança OAuthFlowException quando a twitch nega o client secret', function (): void {
    $service = makeAppTokenService(new MockClient([
        GetAppAccessToken::class => MockResponse::make([
            'status' => 403,
            'message' => 'invalid client secret',
        ], 403),
    ]));

    expect(fn (): string => $service->getToken())
        ->toThrow(OAuthFlowException::class, 'invalid client secret');

    expect(Cache::has('twitch_app_access_token'))->toBeFalse();
});
