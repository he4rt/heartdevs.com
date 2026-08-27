<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Actions\RegisterTwitchSubscriptionsAction;
use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

use function Pest\Laravel\assertDatabaseHas;

function bindMockedHelix(MockClient $mock): void
{
    // The app token is resolved lazily; seed the cache so getToken() never
    // reaches the real Twitch OAuth endpoint.
    Cache::put('twitch_app_access_token', 'fake-token', 3_600);

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(
            tokenService: new TwitchAppTokenService(
                new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret'),
            ),
            clientId: 'fake-client-id',
        ),
        fn (TwitchHelixConnector $connector) => $connector->withMockClient($mock),
    ));
}

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_callback', 'https://example.com/api/webhooks/twitch/eventsub');
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');
});

test('registra a subscription usando o broadcaster id informado, sem tocar em config', function (): void {
    bindMockedHelix(new MockClient([
        CreateSubscription::class => MockResponse::make([
            'data' => [[
                'id' => 'sub-abc',
                'type' => 'stream.online',
                'status' => 'enabled',
                'condition' => ['broadcaster_user_id' => '999888'],
                'transport' => ['method' => 'webhook', 'callback' => 'https://example.com/api/webhooks/twitch/eventsub'],
                'cost' => 0,
                'version' => '1',
            ]],
        ], 202),
    ]));

    $result = resolve(RegisterTwitchSubscriptionsAction::class)('999888', [TwitchEventSubType::StreamOnline]);

    expect($result['created'])->toBe(1)
        ->and($result['failed'])->toBe(0);

    assertDatabaseHas(TwitchSubscription::class, [
        'subscription_id' => 'sub-abc',
        'broadcaster_user_id' => '999888',
        'type' => 'stream.online',
    ]);
});

test('pula subscriptions já existentes para o mesmo broadcaster', function (): void {
    TwitchSubscription::query()->create([
        'subscription_id' => 'existing-1',
        'type' => 'stream.online',
        'status' => 'enabled',
        'broadcaster_user_id' => '999888',
        'condition' => ['broadcaster_user_id' => '999888'],
        'transport' => 'webhook',
        'callback_url' => 'https://example.com/api/webhooks/twitch/eventsub',
        'cost' => 0,
        'version' => '1',
    ]);

    bindMockedHelix(new MockClient([
        CreateSubscription::class => MockResponse::make(['data' => [['id' => 'should-not-be-used']]], 202),
    ]));

    $result = resolve(RegisterTwitchSubscriptionsAction::class)('999888', [TwitchEventSubType::StreamOnline]);

    expect($result['created'])->toBe(0)
        ->and($result['skipped'])->toBe(1);
});
