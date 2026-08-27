<?php

declare(strict_types=1);

use He4rt\IntegrationWhatsapp\Models\WhatsAppEventLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config()->set('whatsapp.webhook_secret', 'test-secret');
});

/**
 * @param  array<string, mixed>  $payload
 */
function postEvent(array $payload, ?string $signature = null, ?string $eventId = null): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $eventId ??= (string) Str::uuid();
    // assina event_id + corpo (mesmo material do coletor) — ver ADR-0003.
    $signature ??= hash_hmac('sha256', $eventId.'.'.$body, 'test-secret');

    return test()->call(
        method: 'POST',
        uri: '/api/webhooks/whatsapp',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
            'HTTP_X_EVENT_ID' => $eventId,
        ],
        content: $body,
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function messagesUpsertEvent(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'messages.upsert',
        'chat_jid' => '120363000000000000@g.us',
        'payload' => [
            'key' => [
                'remoteJid' => '120363000000000000@g.us',
                'participant' => '5511999999999@s.whatsapp.net',
                'id' => 'MSG1',
            ],
            'messageTimestamp' => 1_700_000_000,
            'message' => ['conversation' => 'oi'],
        ],
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function groupsMetadataSnapshot(array $overrides = []): array
{
    return array_replace_recursive([
        'type' => 'groups.metadata',
        'chat_jid' => '120363000000000000@g.us',
        'payload' => [
            'id' => '120363000000000000@g.us',
            'subject' => 'He4rt Devs',
            'desc' => 'comunidade',
            'participants' => [
                ['id' => '100000000000001@lid', 'admin' => 'superadmin'],
                ['id' => '100000000000002@lid', 'admin' => null],
            ],
        ],
    ], $overrides);
}

test('accepts a valid signed event and persists it synchronously (201)', function (): void {
    $response = postEvent(messagesUpsertEvent());

    $response->assertCreated()->assertJson(['status' => 'stored']);

    // 2xx só após o commit: a linha já existe quando a resposta chega.
    expect(WhatsAppEventLog::query()->count())->toBe(1);
});

test('persists the raw event end-to-end', function (): void {
    postEvent(messagesUpsertEvent());

    $event = WhatsAppEventLog::query()->sole();

    expect($event->type)->toBe('messages.upsert')
        ->and($event->chat_jid)->toBe('120363000000000000@g.us')
        ->and($event->payload['message']['conversation'])->toBe('oi')
        ->and($event->received_at)->not->toBeNull();
});

test('stores a groups.metadata snapshot as a raw event without special processing', function (): void {
    postEvent(groupsMetadataSnapshot())->assertCreated();

    $event = WhatsAppEventLog::query()->sole();

    expect($event->type)->toBe('groups.metadata')
        ->and($event->chat_jid)->toBe('120363000000000000@g.us')
        ->and($event->payload['participants'])->toHaveCount(2);
});

test('sanitizes a poison payload (NUL byte) end-to-end and still returns 201', function (): void {
    $response = postEvent(messagesUpsertEvent([
        'payload' => ['message' => ['conversation' => "a\0b"]],
    ]));

    $response->assertCreated();

    $event = WhatsAppEventLog::query()->sole();
    expect($event->payload['message']['conversation'])->toBe('ab');
});

test('rejects an event with a non-uuid event id', function (): void {
    postEvent(messagesUpsertEvent(), eventId: 'not-a-uuid')->assertBadRequest();

    expect(WhatsAppEventLog::query()->count())->toBe(0);
});

test('rejects an event with an invalid signature', function (): void {
    postEvent(messagesUpsertEvent(), signature: 'deadbeef')->assertUnauthorized();

    expect(WhatsAppEventLog::query()->count())->toBe(0);
});

test('rejects a signature that does not cover the event id (HMAC must include event_id)', function (): void {
    $event = messagesUpsertEvent();
    $body = json_encode($event, JSON_THROW_ON_ERROR);
    $eventId = (string) Str::uuid();
    // esquema antigo: assina SÓ o corpo → deve falhar agora que o HMAC cobre o event_id.
    $bodyOnlySignature = hash_hmac('sha256', $body, 'test-secret');

    postEvent($event, signature: $bodyOnlySignature, eventId: $eventId)->assertUnauthorized();

    expect(WhatsAppEventLog::query()->count())->toBe(0);
});

test('rejects an event missing the X-Event-Id header', function (): void {
    $body = json_encode(messagesUpsertEvent(), JSON_THROW_ON_ERROR);

    $response = test()->call(
        method: 'POST',
        uri: '/api/webhooks/whatsapp',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $body, 'test-secret'),
        ],
        content: $body,
    );

    $response->assertUnauthorized();

    expect(WhatsAppEventLog::query()->count())->toBe(0);
});

test('returns 422 when a required field is missing', function (string $missingField): void {
    postEvent(Arr::except(messagesUpsertEvent(), $missingField))->assertUnprocessable();

    expect(WhatsAppEventLog::query()->count())->toBe(0);
})->with([
    'missing type' => ['type'],
    'missing payload' => ['payload'],
]);

test('a duplicate event_id is deduplicated synchronously', function (): void {
    $eventId = (string) Str::uuid();

    postEvent(messagesUpsertEvent(), eventId: $eventId)->assertCreated();
    postEvent(messagesUpsertEvent(), eventId: $eventId)->assertCreated();

    expect(WhatsAppEventLog::query()->where('event_id', $eventId)->count())->toBe(1);
});
