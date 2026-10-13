<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\DTOs\IncomingStreamEvent;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\RecordStreamEvent;
use He4rt\Streaming\StreamEvent\Actions\TriggerTestAlert;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([AlertTriggered::class]);
});

function incomingEventFor(StreamerSource $source, array $overrides = []): IncomingStreamEvent
{
    return new IncomingStreamEvent(...[
        'platform' => IdentityProvider::Twitch,
        'broadcasterId' => $source->identity->external_account_id,
        'sourceEventId' => 'm-1',
        'type' => StreamEventType::Follow,
        'occurredAt' => CarbonImmutable::now(),
        'actor' => new StreamActor(platformId: '9911', login: 'mariacoda', displayName: 'MariaCoda'),
        ...$overrides,
    ]);
}

test('um follow novo é gravado e vira alerta no canal do streamer', function (): void {
    $source = StreamerSource::factory()->create();

    $event = resolve(RecordStreamEvent::class)->handle(incomingEventFor($source));

    expect($event?->type)->toBe(StreamEventType::Follow)
        ->and($event?->actor_login)->toBe('mariacoda')
        ->and($event?->streamer_id)->toBe($source->streamer_id);
    Event::assertDispatched(fn (AlertTriggered $alert): bool => $alert->broadcastOn()[0]->name === 'private-'.$source->streamer->overlayChannel()
        && $alert->broadcastAs() === 'alert.triggered'
        && $alert->broadcastWith() === [
            'type' => 'follow',
            'actor' => ['login' => 'mariacoda', 'displayName' => 'MariaCoda'],
            'details' => null,
            'isTest' => false,
        ]);
});

test('o reenvio da Twitch não duplica o evento nem o alerta', function (): void {
    $source = StreamerSource::factory()->create();
    $record = resolve(RecordStreamEvent::class);

    $record->handle(incomingEventFor($source));
    $record->handle(incomingEventFor($source));

    expect(StreamEvent::query()->count())->toBe(1);
    Event::assertDispatchedTimes(AlertTriggered::class, 1);
});

test('a fonte desligada grava o evento sem alertar', function (): void {
    $source = StreamerSource::factory()->disabled()->create();

    $event = resolve(RecordStreamEvent::class)->handle(incomingEventFor($source, [
        'type' => StreamEventType::Cheer,
        'details' => new CheerDetails(bits: 500),
    ]));

    expect($event)->not->toBeNull();
    Event::assertNotDispatched(AlertTriggered::class);
});

test('o tipo de alerta desligado grava o evento sem alertar', function (): void {
    $source = StreamerSource::factory()->create();
    $streamer = $source->streamer;
    $streamer->update(['settings' => $streamer->settings->withAlerts($streamer->settings->alerts->with(StreamEventType::Raid, enabled: false))]);

    $event = resolve(RecordStreamEvent::class)->handle(incomingEventFor($source, ['type' => StreamEventType::Raid]));

    expect($event)->not->toBeNull();
    Event::assertNotDispatched(AlertTriggered::class);
});

test('o streamer desativado não grava evento', function (): void {
    $source = StreamerSource::factory()->for(Streamer::factory()->disabled())->create();

    expect(resolve(RecordStreamEvent::class)->handle(incomingEventFor($source)))->toBeNull()
        ->and(StreamEvent::query()->exists())->toBeFalse();
});

test('um canal que não é de streamer não grava evento', function (): void {
    $event = resolve(RecordStreamEvent::class)->handle(new IncomingStreamEvent(
        platform: IdentityProvider::Twitch,
        broadcasterId: '227168488',
        sourceEventId: 'm-1',
        type: StreamEventType::Follow,
        occurredAt: CarbonImmutable::now(),
    ));

    expect($event)->toBeNull()
        ->and(StreamEvent::query()->exists())->toBeFalse();
});

test('o cheer anônimo é gravado sem ator', function (): void {
    $source = StreamerSource::factory()->create();

    $event = resolve(RecordStreamEvent::class)->handle(incomingEventFor($source, [
        'type' => StreamEventType::Cheer,
        'actor' => null,
        'details' => new CheerDetails(bits: 100),
    ]));

    expect($event?->actor())->toBeNull()
        ->and($event?->details?->summary())->toBe('100 bits');
});

test('o evento durante a live aponta para a sessão aberta', function (): void {
    $source = StreamerSource::factory()->create();
    $session = StreamSession::factory()->forSource($source)->create();

    $event = resolve(RecordStreamEvent::class)->handle(incomingEventFor($source, [
        'type' => StreamEventType::Sub,
        'details' => new SubDetails(months: 3),
    ]));

    expect($event?->stream_session_id)->toBe($session->getKey());
});

test('o alerta de teste vai para a overlay sem gravar evento', function (): void {
    $streamer = Streamer::factory()->create();

    resolve(TriggerTestAlert::class)->handle($streamer, StreamEventType::Cheer);

    expect(StreamEvent::query()->exists())->toBeFalse();
    Event::assertDispatched(fn (AlertTriggered $alert): bool => $alert->isTest
        && $alert->broadcastWith()['details'] === ['bits' => 500, 'message' => null]);
});
