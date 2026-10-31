<?php

declare(strict_types=1);

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Data\PubSettings;
use He4rt\Streaming\Streamer\Data\StreamerSettings;

test('guarda só um hash de evento válido', function (?string $hash, ?string $expected): void {
    expect(PubSettings::fromArray(['slido_event_hash' => $hash])->slidoEventHash)->toBe($expected);
})->with([
    'hash válido' => ['jC9L7Lu93Vw3ssNhB92Pvb', 'jC9L7Lu93Vw3ssNhB92Pvb'],
    'sem hash' => [null, null],
    'url no lugar do hash' => ['https://app.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb', null],
]);

test('a cena da He4rt Pub entra nas configurações do streamer sem mexer nas outras', function (): void {
    $settings = new StreamerSettings()->withScene(new PubSettings('jC9L7Lu93Vw3ssNhB92Pvb'));

    $restored = StreamerSettings::fromArray($settings->toArray());

    expect($restored->forScene(OverlayScene::Pub)->toArray())->toBe(['slido_event_hash' => 'jC9L7Lu93Vw3ssNhB92Pvb'])
        ->and($restored->chat->toArray())->toBe(new StreamerSettings()->chat->toArray());
});
