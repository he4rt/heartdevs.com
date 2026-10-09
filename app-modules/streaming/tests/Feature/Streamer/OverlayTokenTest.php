<?php

declare(strict_types=1);

use He4rt\Streaming\Streamer\Actions\RegenerateOverlayToken;
use He4rt\Streaming\Streamer\Actions\ResolveOverlayToken;
use He4rt\Streaming\Streamer\Models\Streamer;

test('o token válido resolve o streamer ativo', function (): void {
    $streamer = Streamer::factory()->withToken('abcdefghijklmnopqrstuvwxyz012345')->create();

    expect(resolve(ResolveOverlayToken::class)->handle('abcdefghijklmnopqrstuvwxyz012345')?->is($streamer))->toBeTrue();
});

test('o token de um streamer desativado não resolve', function (): void {
    Streamer::factory()->disabled()->withToken('abcdefghijklmnopqrstuvwxyz012345')->create();

    expect(resolve(ResolveOverlayToken::class)->handle('abcdefghijklmnopqrstuvwxyz012345'))->toBeNull();
});

test('regenerar invalida o token antigo e troca o canal', function (): void {
    $streamer = Streamer::factory()->withToken('abcdefghijklmnopqrstuvwxyz012345')->create();
    $oldChannel = $streamer->overlayChannel();

    $newToken = resolve(RegenerateOverlayToken::class)->handle($streamer);

    expect(resolve(ResolveOverlayToken::class)->handle('abcdefghijklmnopqrstuvwxyz012345'))->toBeNull()
        ->and(resolve(ResolveOverlayToken::class)->handle($newToken)?->is($streamer))->toBeTrue()
        ->and($newToken)->toMatch('/^[a-z0-9]{32}$/')
        ->and($streamer->overlayChannel())->not->toBe($oldChannel);
});

test('o canal da overlay leva o id e a impressão do token', function (): void {
    $streamer = Streamer::factory()->create();

    expect($streamer->overlayChannel())
        ->toBe(sprintf('overlay.%s.%s', $streamer->getKey(), mb_substr($streamer->overlay_token_hash, 0, 12)))
        ->not->toContain($streamer->overlay_token);
});
