<?php

declare(strict_types=1);

use He4rt\BotDiscord\Events\RawGatewayEvent;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('persists a dispatch event to discord_event_logs', function (): void {
    $payload = (object) [
        'op' => 0,
        't' => 'GUILD_MEMBER_ADD',
        's' => 42,
        'd' => (object) [
            'guild_id' => '123456789',
            'user_id' => '987654321',
            'channel_id' => null,
            'roles' => [],
        ],
    ];

    (new RawGatewayEvent)->handle($payload);

    $log = DiscordEventLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->event_type)->toBe('GUILD_MEMBER_ADD')
        ->and($log->guild_id)->toBe('123456789')
        ->and($log->user_id)->toBe('987654321')
        ->and($log->payload)->toBeArray()
        ->and($log->payload['roles'])->toBeEmpty();
});

test('extracts user_id from author when user_id is absent', function (): void {
    $payload = (object) [
        'op' => 0,
        't' => 'MESSAGE_CREATE',
        's' => 43,
        'd' => (object) [
            'guild_id' => '123456789',
            'channel_id' => '111222333',
            'content' => 'hello',
            'author' => (object) ['id' => '555666777'],
        ],
    ];

    (new RawGatewayEvent)->handle($payload);

    $log = DiscordEventLog::query()->first();

    expect($log->user_id)->toBe('555666777')
        ->and($log->channel_id)->toBe('111222333');
});

test('skips non-dispatch events without type', function (): void {
    $payload = (object) [
        'op' => 11,
        'd' => null,
    ];

    (new RawGatewayEvent)->handle($payload);

    expect(DiscordEventLog::query()->count())->toBe(0);
});

test('skips heartbeat ack events', function (): void {
    $payload = (object) [
        'op' => 1,
        't' => null,
        'd' => null,
    ];

    (new RawGatewayEvent)->handle($payload);

    expect(DiscordEventLog::query()->count())->toBe(0);
});
