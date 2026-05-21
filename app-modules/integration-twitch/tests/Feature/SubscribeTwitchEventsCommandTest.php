<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Enums\TwitchEventSubType;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\CreateSubscription;
use He4rt\IntegrationTwitch\Transport\Requests\EventSub\ListSubscriptions;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function mockEventSubResponses(array $existingSubscriptions = []): MockClient
{
    $mock = new MockClient([
        ListSubscriptions::class => MockResponse::make([
            'data' => $existingSubscriptions,
            'total' => count($existingSubscriptions),
        ]),
        CreateSubscription::class => MockResponse::make([
            'data' => [['id' => 'sub-123', 'status' => 'webhook_callback_verification_pending']],
            'total' => 1,
        ], 202),
    ]);

    app()->instance(TwitchHelixConnector::class, tap(
        new TwitchHelixConnector(appToken: 'fake-token', clientId: 'fake-client-id'),
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

test('fails without type or all flag', function (): void {
    mockEventSubResponses();

    $this->artisan('twitch:subscribe', ['broadcaster_user_id' => '12345'])
        ->assertFailed();
});

test('enum version returns correct values', function (): void {
    expect(TwitchEventSubType::StreamOnline->version())->toBe('1')
        ->and(TwitchEventSubType::ChannelFollow->version())->toBe('2')
        ->and(TwitchEventSubType::ChannelUpdate->version())->toBe('2')
        ->and(TwitchEventSubType::ChannelSubscribe->version())->toBe('1');
});

test('enum condition returns correct structure', function (): void {
    $simple = TwitchEventSubType::StreamOnline->condition('12345');
    expect($simple)->toBe(['broadcaster_user_id' => '12345']);

    $withUser = TwitchEventSubType::ChannelFollow->condition('12345', '67890');
    expect($withUser)->toBe([
        'broadcaster_user_id' => '12345',
        'user_id' => '67890',
    ]);

    $raid = TwitchEventSubType::ChannelRaid->condition('12345');
    expect($raid)->toBe(['to_broadcaster_user_id' => '12345']);

    $moderator = TwitchEventSubType::ChannelModeratorAdd->condition('12345');
    expect($moderator)->toBe([
        'broadcaster_user_id' => '12345',
        'moderator_user_id' => '12345',
    ]);
});
