<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\DeleteSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function mockEventSubResponses(array $existingSubscriptions = [], ?MockResponse $createResponse = null): MockClient
{
    $mock = new MockClient([
        ListSubscriptions::class => MockResponse::make([
            'data' => $existingSubscriptions,
            'total' => count($existingSubscriptions),
        ]),
        CreateSubscription::class => $createResponse ?? MockResponse::make([
            'data' => [['id' => 'sub-123', 'status' => 'webhook_callback_verification_pending']],
            'total' => 1,
        ], 202),
        DeleteSubscription::class => MockResponse::make([], 204),
    ]);

    // The app token is resolved lazily by the connector; seed the cache so
    // getToken() short-circuits without hitting the real Twitch OAuth endpoint.
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

    return $mock;
}

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_callback', 'https://example.com/api/webhooks/twitch/eventsub');
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');
});

test('subscribes to a specific event type', function (): void {
    $mock = mockEventSubResponses();

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--type' => 'stream.online',
    ])->assertSuccessful();

    $mock->assertSentCount(2);
});

test('subscribes to all event types', function (): void {
    $mock = mockEventSubResponses();
    $totalTypes = count(TwitchEventSubType::cases());

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--all' => true,
    ])->assertSuccessful();

    $mock->assertSent(ListSubscriptions::class);
    $mock->assertSent(CreateSubscription::class);
});

test('skips already existing subscriptions', function (): void {
    $mock = mockEventSubResponses([
        [
            'type' => 'stream.online',
            'condition' => ['broadcaster_user_id' => '12345'],
        ],
    ]);

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--type' => 'stream.online',
    ])->assertSuccessful()
        ->expectsOutputToContain('already_exists');
});

test('reports a rejected creation instead of counting it as created', function (): void {
    mockEventSubResponses(createResponse: MockResponse::make(['message' => 'missing scope'], 403));

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--type' => 'channel.follow',
    ])->assertSuccessful()
        ->expectsOutputToContain('missing_scope')
        ->expectsOutputToContain('0 subscription(s) created.');
});

test('fails without type, all, or clear-all flag', function (): void {
    mockEventSubResponses();

    $this->artisan('twitch:subscribe', ['broadcaster_user_id' => '12345'])
        ->assertFailed();
});

test('clear-all deletes all subscriptions for broadcaster', function (): void {
    $mock = mockEventSubResponses([
        [
            'id' => 'sub-aaa',
            'type' => 'stream.online',
            'condition' => ['broadcaster_user_id' => '12345'],
        ],
        [
            'id' => 'sub-bbb',
            'type' => 'channel.follow',
            'condition' => ['broadcaster_user_id' => '12345'],
        ],
    ]);

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--clear-all' => true,
    ])->assertSuccessful()
        ->expectsOutputToContain('2 subscription(s) deleted.');

    $mock->assertSent(DeleteSubscription::class);
});

test('clear-all with no subscriptions shows info message', function (): void {
    mockEventSubResponses();

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--clear-all' => true,
    ])->assertSuccessful()
        ->expectsOutputToContain('No subscriptions found');
});

test('enum getVersion returns correct values', function (): void {
    expect(TwitchEventSubType::StreamOnline->getVersion())->toBe('1')
        ->and(TwitchEventSubType::ChannelFollow->getVersion())->toBe('2')
        ->and(TwitchEventSubType::ChannelUpdate->getVersion())->toBe('2')
        ->and(TwitchEventSubType::ChannelSubscribe->getVersion())->toBe('1');
});

test('enum getCondition returns correct structure', function (): void {
    $simple = TwitchEventSubType::StreamOnline->getCondition('12345');
    expect($simple)->toBe(['broadcaster_user_id' => '12345']);

    $withModerator = TwitchEventSubType::ChannelFollow->getCondition('12345', '67890');
    expect($withModerator)->toBe([
        'broadcaster_user_id' => '12345',
        'moderator_user_id' => '67890',
    ]);

    $chatMessage = TwitchEventSubType::ChannelChatMessage->getCondition('12345', '67890');
    expect($chatMessage)->toBe([
        'broadcaster_user_id' => '12345',
        'user_id' => '67890',
    ]);

    $raid = TwitchEventSubType::ChannelRaid->getCondition('12345');
    expect($raid)->toBe(['to_broadcaster_user_id' => '12345']);

    $moderatorAdd = TwitchEventSubType::ChannelModeratorAdd->getCondition('12345');
    expect($moderatorAdd)->toBe(['broadcaster_user_id' => '12345']);

    $shieldMode = TwitchEventSubType::ChannelShieldModeBegin->getCondition('12345', '67890');
    expect($shieldMode)->toBe([
        'broadcaster_user_id' => '12345',
        'moderator_user_id' => '67890',
    ]);
});

test('lê todas as páginas de inscrições do broadcaster antes de limpar', function (): void {
    $subscription = fn (string $id): array => [
        'id' => $id,
        'type' => 'stream.online',
        'condition' => ['broadcaster_user_id' => '12345'],
    ];

    $mock = new MockClient([
        MockResponse::make(['data' => [$subscription('sub-1')], 'pagination' => ['cursor' => 'page-2']]),
        MockResponse::make(['data' => [$subscription('sub-2')], 'pagination' => []]),
        MockResponse::make([], 204),
        MockResponse::make([], 204),
    ]);

    Cache::put('twitch_app_access_token', 'fake-token', 3_600);

    app()->instance(TwitchHelixConnector::class, new TwitchHelixConnector(
        tokenService: new TwitchAppTokenService(new TwitchOAuthConnector(clientId: 'fake-client-id', clientSecret: 'fake-secret')),
        clientId: 'fake-client-id',
    )->withMockClient($mock));

    $this->artisan('twitch:subscribe', [
        'broadcaster_user_id' => '12345',
        '--clear-all' => true,
    ])->assertSuccessful();

    $mock->assertSentCount(4);
    $mock->assertSent(fn ($request): bool => $request instanceof ListSubscriptions
        && $request->query()->get('user_id') === '12345'
        && $request->query()->get('after') === null);
    $mock->assertSent(fn ($request): bool => $request instanceof ListSubscriptions
        && $request->query()->get('after') === 'page-2');
    $mock->assertSent(fn ($request): bool => $request instanceof DeleteSubscription);
});
