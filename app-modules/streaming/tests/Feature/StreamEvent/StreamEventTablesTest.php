<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\SubTier;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Database\UniqueConstraintViolationException;

test('os detalhes voltam do banco com o VO do tipo', function (StreamEventType $type, StreamEventDetails $details, string $summary): void {
    $event = StreamEvent::factory()->ofType($type, $details)->create();

    $restored = $event->fresh()?->details;

    expect($restored)->toEqual($details)
        ->and($restored?->summary())->toBe($summary);
})->with([
    'sub' => [StreamEventType::Sub, new SubDetails(SubTier::Tier1, months: 3), '3 meses'],
    'gift sub' => [StreamEventType::GiftSub, new GiftSubDetails(SubTier::Tier1, total: 5), '5 subs'],
    'cheer' => [StreamEventType::Cheer, new CheerDetails(bits: 500), '500 bits'],
    'raid' => [StreamEventType::Raid, new RaidDetails(viewers: 42), '+42 viewers'],
]);

test('follow não tem detalhes', function (): void {
    $event = StreamEvent::factory()->ofType(StreamEventType::Follow)->create();

    expect($event->fresh()?->details)->toBeNull();
});

test('o evento não tem updated_at', function (): void {
    $event = StreamEvent::factory()->create();

    expect($event->fresh()?->getAttributes())->not->toHaveKey('updated_at');
});

test('a mesma origem não grava dois eventos da mesma identidade', function (): void {
    $event = StreamEvent::factory()->create();

    StreamEvent::factory()->create([
        'streamer_id' => $event->streamer_id,
        'external_identity_id' => $event->external_identity_id,
        'source_event_id' => $event->source_event_id,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('uma identidade tem uma sessão aberta só', function (): void {
    $source = StreamerSource::factory()->create();
    StreamSession::factory()->forSource($source)->create();

    StreamSession::factory()->forSource($source)->create();
})->throws(UniqueConstraintViolationException::class);

test('sessões fechadas da mesma identidade convivem', function (): void {
    $source = StreamerSource::factory()->create();

    StreamSession::factory()->forSource($source)->ended()->count(2)->create();
    StreamSession::factory()->forSource($source)->create();

    expect(StreamSession::query()->count())->toBe(3);
});
