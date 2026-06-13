<?php

declare(strict_types=1);

use He4rt\IntegrationWhatsapp\Actions\StoreWhatsAppEventAction;
use He4rt\IntegrationWhatsapp\Models\WhatsAppEventLog;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $overrides
 */
function runAction(array $overrides = []): WhatsAppEventLog
{
    $defaults = [
        'eventId' => (string) Str::uuid(),
        'type' => 'messages.upsert',
        'chatJid' => '120363000000000000@g.us',
        'payload' => ['message' => ['conversation' => 'oi']],
    ];

    $data = array_merge($defaults, $overrides);

    return resolve(StoreWhatsAppEventAction::class)->execute(
        eventId: $data['eventId'],
        type: $data['type'],
        chatJid: $data['chatJid'],
        payload: $data['payload'],
    );
}

test('stores the raw event synchronously', function (): void {
    runAction();

    $event = WhatsAppEventLog::query()->sole();

    expect($event->type)->toBe('messages.upsert')
        ->and($event->chat_jid)->toBe('120363000000000000@g.us')
        ->and($event->payload['message']['conversation'])->toBe('oi')
        ->and($event->received_at)->not->toBeNull();
});

test('handles a null chat_jid', function (): void {
    runAction(['chatJid' => null]);

    expect(WhatsAppEventLog::query()->sole()->chat_jid)->toBeNull();
});

test('is idempotent on event_id (sink-side dedup)', function (): void {
    $eventId = (string) Str::uuid();

    runAction(['eventId' => $eventId]);
    runAction(['eventId' => $eventId]);

    expect(WhatsAppEventLog::query()->where('event_id', $eventId)->count())->toBe(1);
});

test('stores distinct events as separate rows', function (): void {
    runAction(['eventId' => (string) Str::uuid()]);
    runAction(['eventId' => (string) Str::uuid()]);

    expect(WhatsAppEventLog::query()->count())->toBe(2);
});

test('sanitizes NUL bytes that jsonb would reject, preserving the rest', function (): void {
    runAction(['payload' => ['message' => ['conversation' => "a\0b"]]]);

    $event = WhatsAppEventLog::query()->sole();

    expect($event->payload['message']['conversation'])->toBe('ab');
});

test('sanitizes invalid UTF-8 without throwing', function (): void {
    runAction(['payload' => ['message' => ['conversation' => "ola\xFFmundo"]]]);

    $event = WhatsAppEventLog::query()->sole();

    expect($event->payload['message']['conversation'])->toContain('ola')
        ->and(mb_check_encoding($event->payload['message']['conversation'], 'UTF-8'))->toBeTrue();
});

test('sanitizes NUL bytes in array keys', function (): void {
    runAction(['payload' => ["a\0b" => 'value']]);

    $event = WhatsAppEventLog::query()->sole();

    expect($event->payload)->toHaveKey('ab');
});
