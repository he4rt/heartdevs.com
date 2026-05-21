<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Models\TwitchEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

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

    $headers = signedTwitchHeaders($body, 'notification', null, $timestamp);

    $this->postJson('/api/webhooks/twitch/eventsub', $payload, $headers)
        ->assertStatus(403);
});

test('returns challenge for webhook verification', function (): void {
    $payload = ['challenge' => 'test-challenge-string', 'subscription' => ['type' => 'stream.online']];

    postTwitchWebhook($payload, 'webhook_callback_verification')->assertOk()
        ->assertSee('test-challenge-string');
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

test('stores revocation event in twitch_event_logs', function (): void {
    $payload = [
        'subscription' => [
            'id' => 'sub-revoked',
            'type' => 'stream.online',
            'version' => '1',
            'status' => 'authorization_revoked',
            'condition' => ['broadcaster_user_id' => '12345'],
            'transport' => ['method' => 'webhook', 'callback' => 'https://example.com'],
        ],
    ];

    postTwitchWebhook($payload, 'revocation')->assertNoContent();

    $log = TwitchEventLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->event_type)->toBe('stream.online');
});

test('handles duplicate twitch_message_id gracefully', function (): void {
    $payload = twitchWebhookPayload();
    $messageId = 'duplicate-msg-id';

    postTwitchWebhook($payload, 'notification', $messageId)->assertNoContent();

    postTwitchWebhook($payload, 'notification', $messageId)->assertNoContent();

    expect(TwitchEventLog::query()->count())->toBe(1);
});
