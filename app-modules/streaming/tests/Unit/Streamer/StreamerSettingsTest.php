<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\VoiceLayout;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Data\StreamerSettings;
use He4rt\Streaming\Streamer\Data\VoiceSettings;

test('configuração vazia vira o padrão', function (): void {
    $settings = StreamerSettings::fromArray([]);

    expect(array_map($settings->alerts->isEnabled(...), StreamEventType::cases()))->each->toBeTrue()
        ->and($settings->startingSoon->title)->toBeNull()
        ->and($settings->startingSoon->startsAt)->toBeNull()
        ->and($settings->voice->layout)->toBe(VoiceLayout::Column);
});

test('um tipo de evento sem valor salvo nasce ligado', function (): void {
    $settings = StreamerSettings::fromArray(['alerts' => ['follow' => false]]);

    expect($settings->alerts->isEnabled(StreamEventType::Raid))->toBeTrue()
        ->and($settings->alerts->isEnabled(StreamEventType::Follow))->toBeFalse();
});

test('um horário fora do formato HH:MM é descartado', function (string $startsAt): void {
    $settings = StreamerSettings::fromArray(['scenes' => ['starting' => ['starts_at' => $startsAt]]]);

    expect($settings->startingSoon->startsAt)->toBeNull();
})->with(['25:99', '7:05', '19h05', '']);

test('valores com o tipo errado viram o padrão', function (): void {
    $settings = StreamerSettings::fromArray([
        'scenes' => ['starting' => ['title' => 42], 'voice' => ['layout' => 'diagonal']],
        'alerts' => ['follow' => 'não'],
    ]);

    expect($settings->startingSoon->title)->toBeNull()
        ->and($settings->voice->layout)->toBe(VoiceLayout::Column)
        ->and($settings->alerts->isEnabled(StreamEventType::Follow))->toBeTrue();
});

test('ida e volta preserva título, horário, layout e alertas', function (): void {
    $settings = new StreamerSettings()
        ->withScene(new StartingSoonSettings(title: '  Live de PHP ', startsAt: '19:05'))
        ->withScene(new VoiceSettings(VoiceLayout::Row))
        ->withAlerts(new StreamerSettings()->alerts->with(StreamEventType::Follow, enabled: false));

    $restored = StreamerSettings::fromArray(json_decode(json_encode($settings->toArray()), associative: true));

    expect($restored->startingSoon->title)->toBe('Live de PHP')
        ->and($restored->startingSoon->startsAt)->toBe('19:05')
        ->and($restored->forScene(OverlayScene::Voice))->toEqual(new VoiceSettings(VoiceLayout::Row))
        ->and($restored->alerts->isEnabled(StreamEventType::Follow))->toBeFalse()
        ->and($restored->alerts->isEnabled(StreamEventType::Sub))->toBeTrue();
});
