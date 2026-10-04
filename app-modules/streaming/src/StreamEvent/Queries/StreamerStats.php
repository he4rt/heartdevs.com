<?php

declare(strict_types=1);

namespace He4rt\Streaming\StreamEvent\Queries;

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;

final readonly class StreamerStats
{
    /**
     * The sub total includes gifted subs. The cheer total is the sum of bits.
     *
     * @return array{follow: int, sub: int, cheer: int, raid: int}
     */
    public function lastDays(Streamer $streamer, int $days): array
    {
        $totals = StreamEvent::query()
            ->whereBelongsTo($streamer)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->toBase()
            ->selectRaw('count(*) filter (where type = ?) as follows', [StreamEventType::Follow->value])
            ->selectRaw(
                "count(*) filter (where type = ?) + coalesce(sum((details->>'total')::int) filter (where type = ?), 0) as subs",
                [StreamEventType::Sub->value, StreamEventType::GiftSub->value],
            )
            ->selectRaw("coalesce(sum((details->>'bits')::int) filter (where type = ?), 0) as bits", [StreamEventType::Cheer->value])
            ->selectRaw('count(*) filter (where type = ?) as raids', [StreamEventType::Raid->value])
            ->first();

        return [
            StreamEventType::Follow->value => $this->total($totals->follows ?? null),
            StreamEventType::Sub->value => $this->total($totals->subs ?? null),
            StreamEventType::Cheer->value => $this->total($totals->bits ?? null),
            StreamEventType::Raid->value => $this->total($totals->raids ?? null),
        ];
    }

    private function total(mixed $aggregate): int
    {
        return is_numeric($aggregate) ? (int) $aggregate : 0;
    }
}
