<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Data\SceneSources;
use He4rt\Streaming\Streamer\Data\StreamerSettings;

test('a cena sem escolha usa todas as fontes', function (): void {
    expect(new SceneSources()->allowedFor(OverlayScene::Pub, ['fonte-a', 'fonte-b']))->toBeNull();
});

test('a cena usa só as fontes escolhidas que ainda existem', function (): void {
    $sceneSources = new SceneSources()->with(OverlayScene::Pub, ['fonte-a', 'fonte-sumiu']);

    expect($sceneSources->allowedFor(OverlayScene::Pub, ['fonte-a', 'fonte-b']))->toBe(['fonte-a'])
        ->and($sceneSources->allowedFor(OverlayScene::Pub, ['fonte-b']))->toBeNull()
        ->and($sceneSources->allowedFor(OverlayScene::Chat, ['fonte-a']))->toBeNull();
});

test('desmarcar todas as fontes volta a cena para todas', function (): void {
    $sceneSources = new SceneSources()->with(OverlayScene::Pub, ['fonte-a'])->with(OverlayScene::Pub, []);

    expect($sceneSources->toArray())->toBeEmpty();
});

test('as fontes por cena sobrevivem ao JSON das configurações e ignoram lixo', function (): void {
    $settings = new StreamerSettings()->withSceneSources(OverlayScene::Pub, ['fonte-a']);
    $payload = [...$settings->toArray(), 'scene_sources' => [...$settings->toArray()['scene_sources'], 'chat' => [42, ''], 'cena-inexistente' => ['fonte-b']]];

    expect(StreamerSettings::fromArray($payload)->sceneSources->toArray())->toBe(['pub' => ['fonte-a']]);
});
