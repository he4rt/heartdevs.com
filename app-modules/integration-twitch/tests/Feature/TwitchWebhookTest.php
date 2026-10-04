<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Enums\TwitchSubscriptionStatus;
use He4rt\IntegrationTwitch\Events\TwitchEventReceived;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use He4rt\IntegrationTwitch\Models\TwitchSubscription;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * @return array<string, array<string, array<string, string>|string>|array<string, string>>
 */
function twitchWebhookPayload(string $eventType = 'stream.online', string $messageType = 'notification'): array
{
    return [
        'subscription' => [
            'id' => 'sub-id-123',
            'type' => $eventType,
            'version' => '1',
            'status' => 'enabled',
            'condition' => [
                'broadcaster_user_id' => '12345',
            ],
            'transport' => [
                'method' => 'webhook',
                'callback' => 'https://example.com/api/webhooks/twitch/eventsub',
            ],
        ],
        'event' => [
            'broadcaster_user_id' => '12345',
            'broadcaster_user_login' => 'danielhe4rt',
            'broadcaster_user_name' => 'danielhe4rt',
            'user_id' => '67890',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function twitchVerificationPayload(): array
{
    return [
        'challenge' => 'test-challenge-string',
        'subscription' => [
            'id' => 'sub-pending',
            'type' => 'stream.online',
            'version' => '1',
            'status' => 'webhook_callback_verification_pending',
            'cost' => 0,
            'condition' => ['broadcaster_user_id' => '12345'],
            'transport' => ['method' => 'webhook', 'callback' => 'https://example.com/api/webhooks/twitch/eventsub'],
        ],
    ];
}

/**
 * @return array<string, array<string, mixed>>
 */
function twitchRevocationPayload(string $status = 'authorization_revoked'): array
{
    return [
        'subscription' => [
            'id' => 'sub-revoked',
            'type' => 'stream.online',
            'version' => '1',
            'status' => $status,
            'cost' => 0,
            'condition' => ['broadcaster_user_id' => '12345'],
            'transport' => ['method' => 'webhook', 'callback' => 'https://example.com'],
        ],
    ];
}

/**
 * @return array<string, string>
 */
function signedTwitchHeaders(string $body, string $messageType = 'notification', ?string $messageId = null, ?string $timestamp = null): array
{
    $messageId ??= (string) Str::uuid();
    $timestamp ??= now()->toIso8601String();
    $secret = config('services.twitch.eventsub_secret', 'test-secret');

    $hmacMessage = $messageId.$timestamp.$body;
    $signature = 'sha256='.hash_hmac('sha256', $hmacMessage, (string) $secret);

    return [
        'Twitch-Eventsub-Message-Id' => $messageId,
        'Twitch-Eventsub-Message-Timestamp' => $timestamp,
        'Twitch-Eventsub-Message-Signature' => $signature,
        'Twitch-Eventsub-Message-Type' => $messageType,
    ];
}

function postTwitchWebhook(array $payload, string $messageType = 'notification', ?string $messageId = null): TestResponse
{
    $body = json_encode($payload);
    $headers = signedTwitchHeaders($body, $messageType, $messageId);

    return test()->postJson(
        '/api/webhooks/twitch/eventsub',
        $payload,
        $headers,
    );
}

beforeEach(function (): void {
    config()->set('services.twitch.eventsub_secret', 'test-secret-at-least-ten-chars');
});

test('rejects request with missing twitch headers', function (): void {
    $this->postJson('/api/webhooks/twitch/eventsub', ['foo' => 'bar'])
        ->assertStatus(403);
});

test('rejects request with invalid signature', function (): void {
    $payload = twitchWebhookPayload();
    $body = json_encode($payload);

    $headers = signedTwitchHeaders($body);
    $headers['Twitch-Eventsub-Message-Signature'] = 'sha256=invalid';

    $this->postJson('/api/webhooks/twitch/eventsub', $payload, $headers)
        ->assertStatus(403);
});

test('rejects request with expired timestamp', function (): void {
    $payload = twitchWebhookPayload();
    $body = json_encode($payload);
    $timestamp = now()->subMinutes(11)->toIso8601String();

    $headers = signedTwitchHeaders($body, 'notification', timestamp: $timestamp);

    $this->postJson('/api/webhooks/twitch/eventsub', $payload, $headers)
        ->assertStatus(403);
});

test('returns challenge for webhook verification', function (): void {
    postTwitchWebhook(twitchVerificationPayload(), 'webhook_callback_verification')->assertOk()
        ->assertSee('test-challenge-string');
});

test('verification enables the pending local subscription', function (): void {
    TwitchSubscription::query()->create([
        'subscription_id' => 'sub-pending',
        'type' => 'stream.online',
        'status' => TwitchSubscriptionStatus::VerificationPending,
        'broadcaster_user_id' => '12345',
        'condition' => ['broadcaster_user_id' => '12345'],
        'transport' => 'webhook',
        'callback_url' => 'https://example.com/api/webhooks/twitch/eventsub',
        'cost' => 0,
        'version' => '1',
    ]);

    postTwitchWebhook(twitchVerificationPayload(), 'webhook_callback_verification')->assertOk();

    expect(TwitchSubscription::query()->sole()->status)->toBe(TwitchSubscriptionStatus::Enabled);
});

test('verification mirrors a subscription created outside the panel', function (): void {
    postTwitchWebhook(twitchVerificationPayload(), 'webhook_callback_verification')->assertOk();

    $subscription = TwitchSubscription::query()->sole();

    expect($subscription->subscription_id)->toBe('sub-pending')
        ->and($subscription->status)->toBe(TwitchSubscriptionStatus::Enabled)
        ->and($subscription->broadcaster_user_id)->toBe('12345');
});

test('stores notification event in twitch_event_logs', function (): void {
    $payload = twitchWebhookPayload('channel.follow');

    postTwitchWebhook($payload)->assertNoContent();

    $log = TwitchEventLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->event_type)->toBe('channel.follow')
        ->and($log->broadcaster_user_id)->toBe('12345')
        ->and($log->user_id)->toBe('67890')
        ->and($log->twitch_message_id)->not->toBeNull()
        ->and($log->payload)->toBeArray();
});

test('dispatches TwitchEventReceived for notifications', function (): void {
    Event::fake([TwitchEventReceived::class]);

    postTwitchWebhook(twitchWebhookPayload('channel.follow'))->assertNoContent();

    Event::assertDispatched(fn (TwitchEventReceived $event): bool => $event->eventLog->event_type === 'channel.follow');
});

test('revocation updates the local subscription status instead of logging an event', function (): void {
    Event::fake([TwitchEventReceived::class]);

    TwitchSubscription::query()->create([
        'subscription_id' => 'sub-revoked',
        'type' => 'stream.online',
        'status' => TwitchSubscriptionStatus::Enabled,
        'broadcaster_user_id' => '12345',
        'condition' => ['broadcaster_user_id' => '12345'],
        'transport' => 'webhook',
        'callback_url' => 'https://example.com',
        'cost' => 0,
        'version' => '1',
    ]);

    postTwitchWebhook(twitchRevocationPayload(), 'revocation')->assertNoContent();

    expect(TwitchSubscription::query()->sole()->status)->toBe(TwitchSubscriptionStatus::AuthorizationRevoked)
        ->and(TwitchEventLog::query()->count())->toBe(0);

    Event::assertNotDispatched(TwitchEventReceived::class);
});

test('revocation of a subscription missing locally creates its mirror', function (): void {
    postTwitchWebhook(twitchRevocationPayload(status: 'user_removed'), 'revocation')->assertNoContent();

    $subscription = TwitchSubscription::query()->sole();

    expect($subscription->subscription_id)->toBe('sub-revoked')
        ->and($subscription->status)->toBe(TwitchSubscriptionStatus::UserRemoved)
        ->and($subscription->broadcaster_user_id)->toBe('12345')
        ->and($subscription->type)->toBe('stream.online');
});

test('handles duplicate twitch_message_id gracefully', function (): void {
    $payload = twitchWebhookPayload();
    $messageId = 'duplicate-msg-id';

    postTwitchWebhook($payload, 'notification', $messageId)->assertNoContent();

    postTwitchWebhook($payload, 'notification', $messageId)->assertNoContent();

    expect(TwitchEventLog::query()->count())->toBe(1);
});

test('extracts chatter_user_id from chat message events', function (): void {
    $payload = [
        'subscription' => [
            'id' => 'sub-chat',
            'type' => 'channel.chat.message',
            'version' => '1',
            'status' => 'enabled',
            'condition' => ['broadcaster_user_id' => '12345', 'user_id' => '12345'],
            'transport' => ['method' => 'webhook', 'callback' => 'https://example.com'],
        ],
        'event' => [
            'broadcaster_user_id' => '12345',
            'broadcaster_user_login' => 'danielhe4rt',
            'broadcaster_user_name' => 'danielhe4rt',
            'chatter_user_id' => '77777',
            'chatter_user_login' => 'viewer_lucas',
            'chatter_user_name' => 'viewer_lucas',
            'message' => ['text' => 'Hello!', 'fragments' => []],
            'message_type' => 'text',
        ],
    ];

    postTwitchWebhook($payload)->assertNoContent();

    $log = TwitchEventLog::query()->latest('id')->first();

    expect($log->user_id)->toBe('77777')
        ->and($log->broadcaster_user_id)->toBe('12345');
});

test('extracts from_broadcaster_user_id from raid events', function (): void {
    $payload = [
        'subscription' => [
            'id' => 'sub-raid',
            'type' => 'channel.raid',
            'version' => '1',
            'status' => 'enabled',
            'condition' => ['to_broadcaster_user_id' => '12345'],
            'transport' => ['method' => 'webhook', 'callback' => 'https://example.com'],
        ],
        'event' => [
            'from_broadcaster_user_id' => '99999',
            'from_broadcaster_user_login' => 'raider',
            'from_broadcaster_user_name' => 'raider',
            'to_broadcaster_user_id' => '12345',
            'to_broadcaster_user_login' => 'danielhe4rt',
            'to_broadcaster_user_name' => 'danielhe4rt',
            'viewers' => 150,
        ],
    ];

    postTwitchWebhook($payload)->assertNoContent();

    $log = TwitchEventLog::query()->latest('id')->first();

    expect($log->user_id)->toBe('99999')
        ->and($log->broadcaster_user_id)->toBe('12345');
});
