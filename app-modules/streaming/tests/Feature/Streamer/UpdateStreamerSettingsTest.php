<?php

declare(strict_types=1);

use He4rt\Streaming\Broadcasting\OverlaySettingsUpdated;
use He4rt\Streaming\Broadcasting\OverlaySourcesUpdated;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSettings;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([OverlaySettingsUpdated::class, OverlaySourcesUpdated::class]);
});

test('salvar uma cena grava e avisa só a overlay dessa cena', function (): void {
    $streamer = Streamer::factory()->create();

    resolve(UpdateStreamerSettings::class)->handle(
        $streamer,
        $streamer->settings->withScene(new StartingSoonSettings('Live de PHP', '19:05')),
    );

    expect($streamer->refresh()->settings->startingSoon->title)->toBe('Live de PHP');
    Event::assertDispatchedTimes(OverlaySettingsUpdated::class, 1);
    Event::assertDispatched(fn (OverlaySettingsUpdated $broadcast): bool => $broadcast->scene === OverlayScene::StartingSoon
        && $broadcast->broadcastOn()[0]->name === 'private-'.$streamer->overlayChannel());
});

test('salvar sem mudar nada nas cenas não avisa a overlay', function (): void {
    $streamer = Streamer::factory()->create();

    resolve(UpdateStreamerSettings::class)->handle(
        $streamer,
        $streamer->settings->withAlerts($streamer->settings->alerts->with(StreamEventType::Raid, enabled: false)),
    );

    expect($streamer->refresh()->settings->alerts->isEnabled(StreamEventType::Raid))->toBeFalse();
    Event::assertNotDispatched(OverlaySettingsUpdated::class);
});

test('mudar as fontes de uma cena manda só essa overlay recarregar', function (): void {
    $streamer = Streamer::factory()->create();

    resolve(UpdateStreamerSettings::class)->handle($streamer, $streamer->settings->withSceneSources(OverlayScene::Pub, ['fonte-he4rtdevs']));

    expect($streamer->refresh()->settings->sceneSources->chosenFor(OverlayScene::Pub))->toBe(['fonte-he4rtdevs']);
    Event::assertDispatchedTimes(OverlaySourcesUpdated::class, 1);
    Event::assertDispatched(fn (OverlaySourcesUpdated $broadcast): bool => $broadcast->scene === OverlayScene::Pub
        && $broadcast->broadcastAs() === 'sources.updated');
    Event::assertNotDispatched(OverlaySettingsUpdated::class);
});
