<?php

declare(strict_types=1);

use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\ReplayStreamEventAlert;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([AlertTriggered::class]);

    $this->source = StreamerSource::factory()->create();
    $this->streamer = $this->source->streamer;
});

test('repete o alerta de um evento gravado sem gravar outro evento', function (): void {
    $event = StreamEvent::factory()->forSource($this->source)->ofType(StreamEventType::Cheer, new CheerDetails(bits: 500))->create();

    resolve(ReplayStreamEventAlert::class)->handle($this->streamer, $event->id);

    Event::assertDispatched(fn (AlertTriggered $alert): bool => !$alert->isTest
        && $alert->type === StreamEventType::Cheer
        && $alert->details instanceof CheerDetails
        && $alert->details->bits === 500
        && $alert->streamer->is($this->streamer));
    expect(StreamEvent::query()->count())->toBe(1);
});

test('não repete o alerta de um evento de outro streamer', function (): void {
    $otherEvent = StreamEvent::factory()->create();

    expect(fn () => resolve(ReplayStreamEventAlert::class)->handle($this->streamer, $otherEvent->id))
        ->toThrow(ModelNotFoundException::class);

    Event::assertNotDispatched(AlertTriggered::class);
});
