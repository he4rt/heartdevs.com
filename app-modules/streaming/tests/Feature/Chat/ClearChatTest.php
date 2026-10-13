<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Streaming\Broadcasting\ChatCleared;
use He4rt\Streaming\Chat\Actions\ClearChat;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;

test('limpar o chat grava a hora e esvazia a overlay', function (): void {
    Event::fake([ChatCleared::class]);
    $streamer = Streamer::factory()->create();
    $clearedAt = CarbonImmutable::parse('2026-10-04 20:00:00');

    resolve(ClearChat::class)->handle($streamer, $clearedAt);

    expect($streamer->refresh()->chat_cleared_at?->equalTo($clearedAt))->toBeTrue();
    Event::assertDispatched(fn (ChatCleared $broadcast): bool => $broadcast->broadcastAs() === 'chat.cleared'
        && $broadcast->broadcastWith() === []
        && $broadcast->streamer->is($streamer));
});
