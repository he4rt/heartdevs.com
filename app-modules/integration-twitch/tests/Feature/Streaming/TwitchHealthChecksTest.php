<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Health\CheckLastTwitchEvent;
use He4rt\IntegrationTwitch\Health\CheckTwitchAccount;
use He4rt\IntegrationTwitch\Health\CheckTwitchSubscriptions;
use He4rt\IntegrationTwitch\Health\CheckWebhookAddress;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\Support\StreamerSubscriptionPlan;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\RefreshUserToken;
use He4rt\IntegrationTwitch\Transport\Requests\OAuth\ValidateToken;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\Streaming\Health\HealthFix;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Str;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

const STREAMER_SCOPES = ['moderator:read:followers', 'channel:read:subscriptions', 'bits:read'];

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_callback', 'https://he4rt.example.com/api/webhooks/twitch/eventsub');
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');

    $this->source = StreamerSource::factory()->create();
});

function fakeTwitchOAuth(array $responses): MockClient
{
    $mock = new MockClient($responses);
    app()->instance(TwitchOAuthConnector::class, new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')->withMockClient($mock));

    return $mock;
}

function subscriptionsWith(StreamerSource $source, TwitchSubscriptionStatus $status, int $minutesAgo = 0): void
{
    foreach (StreamerSubscriptionPlan::for($source) as [$type, $condition]) {
        TwitchSubscription::query()->forceCreate([
            'subscription_id' => (string) Str::uuid(),
            'type' => $type->value,
            'status' => $status,
            'broadcaster_user_id' => $source->identity->external_account_id,
            'condition' => $condition,
            'transport' => 'webhook',
            'cost' => 1,
            'version' => $type->getVersion(),
            'streamer_source_id' => $source->getKey(),
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }
}

function receivedFromTwitch(StreamerSource $source, TwitchEventSubType $type, int $minutesAgo): void
{
    TwitchEventLog::query()->forceCreate([
        'event_type' => $type->value,
        'broadcaster_user_id' => $source->identity->external_account_id,
        'twitch_message_id' => (string) Str::uuid(),
        'payload' => [],
        'created_at' => now()->subMinutes($minutesAgo),
    ]);
}

function goLive(StreamerSource $source): void
{
    StreamSession::factory()->create(['streamer_id' => $source->streamer_id, 'external_identity_id' => $source->external_identity_id]);
}

describe('Conta da Twitch', function (): void {
    test('token válido com todos os escopos está ok', function (): void {
        fakeTwitchOAuth([ValidateToken::class => MockResponse::make(['scopes' => STREAMER_SCOPES])]);

        $check = resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        expect($check->status)->toBe(HealthStatus::Ok)
            ->and($check->detail)->toBe('Autorização válida, 3 de 3 permissões.');
    });

    test('token vencido é renovado antes de virar problema', function (): void {
        $oauth = fakeTwitchOAuth([
            ValidateToken::class => MockResponse::make(['status' => 401, 'message' => 'invalid access token'], 401),
            RefreshUserToken::class => MockResponse::make(['access_token' => 'novo-token', 'refresh_token' => 'novo-refresh', 'expires_in' => 14_000, 'scope' => STREAMER_SCOPES]),
        ]);

        $check = resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        expect($check->status)->toBe(HealthStatus::Ok)
            ->and($this->source->identity->refresh()->credentials->getAccessToken())->toBe('novo-token')
            ->and($this->source->identity->credentials->getRefreshToken())->toBe('novo-refresh');
        $oauth->assertSentCount(1, RefreshUserToken::class);
    });

    test('autorização recusada pede para reconectar', function (): void {
        fakeTwitchOAuth([
            ValidateToken::class => MockResponse::make(['status' => 401], 401),
            RefreshUserToken::class => MockResponse::make(['status' => 400, 'message' => 'Invalid refresh token'], 400),
        ]);

        $check = resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        expect($check->status)->toBe(HealthStatus::Error)
            ->and($check->fix)->toBe(HealthFix::Reconnect);
    });

    test('escopo faltando pede para reconectar', function (): void {
        fakeTwitchOAuth([ValidateToken::class => MockResponse::make(['scopes' => ['bits:read']])]);

        $check = resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        expect($check->status)->toBe(HealthStatus::Warning)
            ->and($check->detail)->toBe('Faltam 2 de 3 permissões.')
            ->and($check->fix)->toBe(HealthFix::Reconnect);
    });

    test('a Twitch fora do ar não vira erro de autorização', function (): void {
        fakeTwitchOAuth([ValidateToken::class => MockResponse::make(['message' => 'down'], 503)]);

        $check = resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        expect($check->status)->toBe(HealthStatus::Warning)
            ->and($check->fix)->toBeNull();
    });

    test('a resposta fica em cache por 10 minutos', function (): void {
        $oauth = fakeTwitchOAuth([ValidateToken::class => MockResponse::make(['scopes' => STREAMER_SCOPES])]);

        resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);
        resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);
        $this->travel(11)->minutes();
        resolve(CheckTwitchAccount::class)->handle($this->source->identity, STREAMER_SCOPES);

        $oauth->assertSentCount(2, ValidateToken::class);
    });
});

describe('Endereço do webhook', function (): void {
    test('a Twitch não alcança um endereço local', function (string $callback): void {
        config()->set('services.twitch.eventsub_callback', $callback);

        $check = resolve(CheckWebhookAddress::class)->handle();

        expect($check->status)->toBe(HealthStatus::Error)
            ->and($check->detail)->toContain('A Twitch não alcança');
    })->with([
        '.test' => 'https://he4rtdevs.test/api/webhooks/twitch/eventsub',
        'localhost' => 'https://localhost/api/webhooks/twitch/eventsub',
        'http' => 'http://he4rt.example.com/api/webhooks/twitch/eventsub',
        'outra porta' => 'https://he4rt.example.com:8443/api/webhooks/twitch/eventsub',
        'ip privado' => 'https://192.168.0.10/api/webhooks/twitch/eventsub',
    ]);

    test('HTTPS público está ok e mostra o endereço', function (): void {
        $check = resolve(CheckWebhookAddress::class)->handle();

        expect($check->status)->toBe(HealthStatus::Ok)
            ->and($check->detail)->toBe('https://he4rt.example.com/api/webhooks/twitch/eventsub');
    });

    test('sem o segredo do webhook, nada funciona', function (): void {
        config()->set('services.twitch.eventsub_secret', '');

        expect(resolve(CheckWebhookAddress::class)->handle()->status)->toBe(HealthStatus::Error);
    });
});

describe('Inscrições', function (): void {
    test('todas ativas está ok', function (): void {
        subscriptionsWith($this->source, TwitchSubscriptionStatus::Enabled);

        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Ok)
            ->and($check->detail)->toBe('9 de 9 ativas');
    });

    test('pendentes recentes estão aguardando a Twitch', function (): void {
        subscriptionsWith($this->source, TwitchSubscriptionStatus::VerificationPending, minutesAgo: 2);

        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Waiting)
            ->and($check->fix)->toBeNull();
    });

    test('pendentes há 15 minutos pedem reparo', function (): void {
        subscriptionsWith($this->source, TwitchSubscriptionStatus::VerificationPending, minutesAgo: 15);

        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Warning)
            ->and($check->detail)->toBe('0 de 9 ativas · 9 pendentes há 15 min')
            ->and($check->fix)->toBe(HealthFix::RepairSubscriptions);
    });

    test('falha na Twitch pede reparo', function (): void {
        subscriptionsWith($this->source, TwitchSubscriptionStatus::NotificationFailuresExceeded);

        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Error)
            ->and($check->fix)->toBe(HealthFix::RepairSubscriptions);
    });

    test('autorização revogada pede para reconectar', function (): void {
        subscriptionsWith($this->source, TwitchSubscriptionStatus::AuthorizationRevoked);

        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Error)
            ->and($check->fix)->toBe(HealthFix::Reconnect);
    });

    test('sem nenhuma inscrição pede reparo', function (): void {
        $check = resolve(CheckTwitchSubscriptions::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Warning)
            ->and($check->detail)->toBe('0 de 9 ativas · faltam 9')
            ->and($check->fix)->toBe(HealthFix::RepairSubscriptions);
    });
});

describe('Último evento', function (): void {
    test('fora da live, silêncio é normal', function (): void {
        receivedFromTwitch($this->source, TwitchEventSubType::ChannelFollow, minutesAgo: 60 * 24 * 3);

        expect(resolve(CheckLastTwitchEvent::class)->handle($this->source)->status)->toBe(HealthStatus::Ok);
    });

    test('nenhum evento fora da live está ok', function (): void {
        expect(resolve(CheckLastTwitchEvent::class)->handle($this->source)->status)->toBe(HealthStatus::Ok);
    });

    test('ao vivo e nada há mais de 15 minutos vira aviso', function (): void {
        goLive($this->source);
        receivedFromTwitch($this->source, TwitchEventSubType::ChannelChatMessage, minutesAgo: 20);

        $check = resolve(CheckLastTwitchEvent::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Warning)
            ->and($check->detail)->toContain('mensagem do chat');
    });

    test('ao vivo com evento recente está ok e diz o que chegou', function (): void {
        goLive($this->source);
        receivedFromTwitch($this->source, TwitchEventSubType::ChannelRaid, minutesAgo: 1);

        $check = resolve(CheckLastTwitchEvent::class)->handle($this->source);

        expect($check->status)->toBe(HealthStatus::Ok)
            ->and($check->detail)->toEndWith('· raid');
    });
});
