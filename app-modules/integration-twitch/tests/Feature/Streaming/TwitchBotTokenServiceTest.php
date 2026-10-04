<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\OAuth\TwitchBotTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\RefreshUserToken;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ValidateToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

beforeEach(function (): void {
    Cache::flush();
    config()->set('services.twitch.bot.user_id', '555000');
    config()->set('services.twitch.bot.refresh_token', 'refresh-from-env');
});

function botTokenService(MockClient $mock): TwitchBotTokenService
{
    return new TwitchBotTokenService(
        new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')->withMockClient($mock),
    );
}

test('o token da conta bot é renovado uma vez e fica em cache', function (): void {
    $mock = new MockClient([
        RefreshUserToken::class => MockResponse::make(['access_token' => 'bot-token', 'refresh_token' => 'refresh-from-env', 'expires_in' => 14_400]),
    ]);
    $service = botTokenService($mock);

    expect($service->getToken())->toBe('bot-token')
        ->and($service->getToken())->toBe('bot-token');
    $mock->assertSentCount(1, RefreshUserToken::class);
});

test('o refresh token novo da Twitch vale para o próximo refresh', function (): void {
    $mock = new MockClient([
        RefreshUserToken::class => MockResponse::make(['access_token' => 'bot-token', 'refresh_token' => 'refresh-rotated', 'expires_in' => 14_400]),
    ]);
    $service = botTokenService($mock);

    $service->getToken();
    Cache::forget('twitch_bot_access_token');
    $service->getToken();

    $mock->assertSent(fn (Request $request): bool => $request instanceof RefreshUserToken && $request->body()->get('refresh_token') === 'refresh-from-env');
    $mock->assertSent(fn (Request $request): bool => $request instanceof RefreshUserToken && $request->body()->get('refresh_token') === 'refresh-rotated');
});

test('a conta bot sem configuração avisa que não está pronta', function (): void {
    config()->set('services.twitch.bot.user_id');

    expect(TwitchBotTokenService::isConfigured())->toBeFalse()
        ->and(TwitchBotTokenService::userId())->toBeNull();
});

test('os escopos da conta bot vêm da validação do token', function (): void {
    $mock = new MockClient([
        RefreshUserToken::class => MockResponse::make(['access_token' => 'bot-token', 'expires_in' => 14_400]),
        ValidateToken::class => MockResponse::make(['client_id' => 'fake-client-id', 'login' => 'he4rtdevs', 'scopes' => ['user:bot', 'user:read:chat'], 'user_id' => '555000']),
    ]);

    expect(botTokenService($mock)->grantedScopes())->toBe(['user:bot', 'user:read:chat']);
    $mock->assertSent(fn (Request $request): bool => $request instanceof ValidateToken && $request->headers()->get('Authorization') === 'OAuth bot-token');
});
