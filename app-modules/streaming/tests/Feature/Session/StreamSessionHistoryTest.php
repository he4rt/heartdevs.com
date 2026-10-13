<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Session\Queries\StreamSessionHistory;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

beforeEach(function (): void {
    $this->source = StreamerSource::factory()->create();
    $this->streamer = $this->source->streamer;
    $this->history = resolve(StreamSessionHistory::class);
});

function historySession(StreamerSource $source, int $daysAgo, bool $ended = true, int $follows = 0): StreamSession
{
    $session = StreamSession::factory()->forSource($source)->create([
        'started_at' => now()->subDays($daysAgo),
        'ended_at' => $ended ? now()->subDays($daysAgo)->addHours(2) : null,
    ]);

    StreamEvent::factory()->count($follows)->forSource($source)->ofType(StreamEventType::Follow)->create([
        'stream_session_id' => $session->id,
        'occurred_at' => $session->started_at->addMinutes(5),
    ]);

    return $session;
}

test('acha a live aberta e a última live encerrada', function (): void {
    $older = historySession($this->source, daysAgo: 3);
    $lastEnded = historySession($this->source, daysAgo: 1);
    $live = historySession($this->source, daysAgo: 0, ended: false);

    expect($this->history->live($this->streamer)?->is($live))->toBeTrue()
        ->and($this->history->lastEnded($this->streamer)?->is($lastEnded))->toBeTrue()
        ->and($this->history->lastEnded($this->streamer)?->is($older))->toBeFalse();
});

test('sem live aberta e sem histórico, não acha nada', function (): void {
    expect($this->history->live($this->streamer))->toBeNull()
        ->and($this->history->lastEnded($this->streamer))->toBeNull();
});

test('navega para a live anterior e a seguinte do mesmo streamer', function (): void {
    $first = historySession($this->source, daysAgo: 3);
    $middle = historySession($this->source, daysAgo: 2);
    $last = historySession($this->source, daysAgo: 1);
    historySession(StreamerSource::factory()->create(), daysAgo: 2);

    expect($this->history->previous($middle)?->is($first))->toBeTrue()
        ->and($this->history->next($middle)?->is($last))->toBeTrue()
        ->and($this->history->previous($first))->toBeNull()
        ->and($this->history->next($last))->toBeNull();
});

test('a base da comparação pula as lives sem dados', function (): void {
    $withData = historySession($this->source, daysAgo: 4, follows: 2);
    historySession($this->source, daysAgo: 3);
    historySession($this->source, daysAgo: 2);
    $current = historySession($this->source, daysAgo: 1, follows: 5);

    $baseline = $this->history->baselineFor($current);

    expect($baseline?->is($withData))->toBeTrue()
        ->and(SessionTotals::of($baseline)->follows)->toBe(2);
});

test('a primeira live com dados não tem base de comparação', function (): void {
    historySession($this->source, daysAgo: 2);
    $current = historySession($this->source, daysAgo: 1, follows: 1);

    expect($this->history->baselineFor($current))->toBeNull();
});
