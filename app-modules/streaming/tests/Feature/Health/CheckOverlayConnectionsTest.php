<?php

declare(strict_types=1);

use He4rt\Streaming\Health\Checks\CheckOverlayConnections;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Health\HealthStatus;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;

function overlaysOpen(?int $count): void
{
    app()->instance(OverlayConnections::class, new readonly class($count) implements OverlayConnections
    {
        public function __construct(private ?int $count) {}

        public function count(Streamer $streamer): ?int
        {
            return $this->count;
        }
    });
}

beforeEach(function (): void {
    $this->streamer = Streamer::factory()->create();
});

test('conta as overlays abertas', function (int $count, string $detail): void {
    overlaysOpen($count);

    $check = resolve(CheckOverlayConnections::class)->handle($this->streamer);

    expect($check->status)->toBe(HealthStatus::Ok)
        ->and($check->detail)->toBe($detail);
})->with([
    'uma' => [1, '1 overlay aberta'],
    'duas' => [2, '2 overlays abertas'],
]);

test('nenhuma overlay fora da live está ok', function (): void {
    overlaysOpen(0);

    expect(resolve(CheckOverlayConnections::class)->handle($this->streamer)->status)->toBe(HealthStatus::Ok);
});

test('ao vivo e sem overlay aberta vira aviso', function (): void {
    overlaysOpen(0);
    StreamSession::factory()->create(['streamer_id' => $this->streamer->id]);

    expect(resolve(CheckOverlayConnections::class)->handle($this->streamer)->status)->toBe(HealthStatus::Warning);
});

test('o servidor de tempo real fora do ar vira erro', function (): void {
    overlaysOpen(count: null);

    expect(resolve(CheckOverlayConnections::class)->handle($this->streamer)->status)->toBe(HealthStatus::Error);
});

test('a live encerrada não conta como ao vivo', function (): void {
    StreamSession::factory()->create(['streamer_id' => $this->streamer->id, 'ended_at' => now()]);

    expect($this->streamer->isLive())->toBeFalse();
});
