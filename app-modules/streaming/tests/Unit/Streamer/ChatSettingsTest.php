<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\ChatAlignment;
use He4rt\Streaming\Enums\ChatDirection;
use He4rt\Streaming\Enums\ChatStyle;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Data\ChatSettings;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Data\StreamerSettings;

test('o streamer sem configuração de chat recebe o padrão', function (): void {
    expect(StreamerSettings::fromArray([])->forScene(OverlayScene::Chat)->toArray())->toBe([
        'style' => 'lines',
        'fade_after_seconds' => 0,
        'font_size' => 24,
        'width' => 480,
        'direction' => 'newest_bottom',
        'alignment' => 'left',
    ]);
});

test('valores fora do limite são corrigidos', function (array $payload, string $field, int $expected): void {
    expect(ChatSettings::fromArray($payload)->toArray()[$field])->toBe($expected);
})->with([
    'fonte grande demais' => [['font_size' => 200], 'font_size', ChatSettings::MAX_FONT_SIZE],
    'fonte pequena demais' => [['font_size' => 8], 'font_size', ChatSettings::MIN_FONT_SIZE],
    'sumir em 2 segundos' => [['fade_after_seconds' => 2], 'fade_after_seconds', ChatSettings::MIN_FADE_SECONDS],
    'sumir em tempo negativo' => [['fade_after_seconds' => -10], 'fade_after_seconds', ChatSettings::NEVER_FADE],
    'sumir depois de uma hora' => [['fade_after_seconds' => 3_600], 'fade_after_seconds', ChatSettings::MAX_FADE_SECONDS],
    'largura estreita demais' => [['width' => 100], 'width', ChatSettings::MIN_WIDTH],
]);

test('números do formulário chegam como texto e são lidos', function (): void {
    $settings = ChatSettings::fromArray(['fade_after_seconds' => '15', 'font_size' => '32', 'width' => '600']);

    expect([$settings->fadeAfterSeconds, $settings->fontSize, $settings->width])->toBe([15, 32, 600]);
});

test('valores com o tipo errado viram o padrão', function (): void {
    $settings = ChatSettings::fromArray(['style' => 'neon', 'font_size' => '24px', 'direction' => 3, 'alignment' => 'center']);

    expect($settings)->toEqual(new ChatSettings());
});

test('salvar o chat não muda as outras cenas', function (): void {
    $settings = new StreamerSettings()
        ->withScene(new StartingSoonSettings(title: 'Live de PHP'))
        ->withScene(new ChatSettings(ChatStyle::Terminal, 15, 28, 560, ChatDirection::NewestAtTop, ChatAlignment::Right));

    $restored = StreamerSettings::fromArray(json_decode(json_encode($settings->toArray()), associative: true));

    expect($restored->startingSoon->title)->toBe('Live de PHP')
        ->and($restored->chat)->toEqual(new ChatSettings(ChatStyle::Terminal, 15, 28, 560, ChatDirection::NewestAtTop, ChatAlignment::Right));
});
