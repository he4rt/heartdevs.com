<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('persists a raw gateway event', function (): void {
    $log = DiscordEventLog::query()->create([
        'event_type' => 'MESSAGE_CREATE',
        'guild_id' => '123456789',
        'user_id' => '987654321',
        'channel_id' => '111222333',
        'payload' => ['content' => 'hello world', 'author' => ['id' => '987654321']],
    ]);

    expect($log)->not->toBeNull()
        ->and($log->event_type)->toBe('MESSAGE_CREATE')
        ->and($log->guild_id)->toBe('123456789')
        ->and($log->payload)->toBeArray()
        ->and($log->payload['content'])->toBe('hello world');
});

test('allows nullable guild_id, user_id, and channel_id', function (): void {
    $log = DiscordEventLog::query()->create([
        'event_type' => 'READY',
        'guild_id' => null,
        'user_id' => null,
        'channel_id' => null,
        'payload' => ['v' => 10, 'session_id' => 'abc'],
    ]);

    expect($log->guild_id)->toBeNull()
        ->and($log->user_id)->toBeNull()
        ->and($log->channel_id)->toBeNull();
});
