<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

it('mostra o estado offline quando não há transmissão', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => false, 'readyTime' => null])]);

    get('/live')
        ->assertOk()
        ->assertSee('Nenhuma live no ar agora')
        ->assertDontSee('data-live-player', escape: false);
});

it('mostra o player quando a live está no ar', function (): void {
    Http::fake(['localhost:9997/*' => Http::response(['ready' => true, 'readyTime' => '2026-08-29T20:00:00Z'])]);

    get('/live')
        ->assertOk()
        ->assertSee('data-live-player', escape: false)
        ->assertSee(config()->string('live.hls_url'), escape: false);
});
