<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use He4rt\Streaming\StreamEvent\Queries\StreamerStats;

test('os números somam gifts nos subs e bits nos cheers dentro da janela', function (): void {
    $source = StreamerSource::factory()->create();
    StreamEvent::factory()->forSource($source)->count(3)->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Sub, new SubDetails(SubTier::Tier2, months: 4))->count(2)->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::GiftSub, new GiftSubDetails(SubTier::Tier1, total: 5))->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Cheer, new CheerDetails(bits: 250))->count(2)->create();
    StreamEvent::factory()->forSource($source)->ofType(StreamEventType::Raid, new RaidDetails(viewers: 42))->create();
    StreamEvent::factory()->forSource($source)->count(10)->create(['occurred_at' => now()->subDays(40)]);
    StreamEvent::factory()->count(6)->create();

    expect(resolve(StreamerStats::class)->lastDays($source->streamer, 30))
        ->toBe(['follow' => 3, 'sub' => 7, 'cheer' => 500, 'raid' => 1]);
});
