<?php

declare(strict_types=1);

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->withoutVite();
});

it('responde 200 na home', function (): void {
    get('/')->assertOk();
});

it('exibe o logo He4rt animado no hero', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('he4rt-logo', escape: false)
        ->assertSee('class="led', escape: false);
});

it('usa o layout do portal com suporte ao tema do sistema', function (): void {
    $html = get('/')->getContent();
    $htmlLang = str_replace('_', '-', app()->getLocale());

    // A tag agora é emitida pelo laravel/head (@head), sem a barra de fechamento.
    expect($html)->toContain('<meta name="color-scheme" content="light dark">')
        ->and($html)->not->toContain('<html lang="'.$htmlLang.'" class="dark">')
        ->and($html)->toContain('flex items-center gap-2 text-text-high')
        ->and($html)->toContain('<span class="text-lg font-bold">He4rt Devs</span>');
});
