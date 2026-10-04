<?php

declare(strict_types=1);

use He4rt\PanelOverlays\Enums\OverlayScene;
use Inertia\Testing\AssertableInertia;

const OVERLAY_TOKEN = '3bfa1a2ce19eadd54f3242055ea3a1a9';

test('cada cena renderiza a sua página de overlay', function (OverlayScene $scene): void {
    $this->get(route('overlays.show', ['token' => OVERLAY_TOKEN, 'scene' => $scene]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component($scene->component())
            ->where('channel', 'he4rtdevs'));
})->with(OverlayScene::cases());

test('a overlay abre sem login, como uma fonte de navegador do OBS', function (): void {
    $this->assertGuest();

    $this->get('/overlay/'.OVERLAY_TOKEN.'/coworking')
        ->assertOk()
        ->assertSee('id="app"', escape: false);
});

test('uma cena desconhecida responde 404', function (): void {
    $this->get('/overlay/'.OVERLAY_TOKEN.'/alertas')->assertNotFound();
});

test('um token fora do formato responde 404', function (string $token): void {
    $this->get('/overlay/'.$token.'/coworking')->assertNotFound();
})->with([
    'curto demais' => ['abc123'],
    'com maiúsculas' => [mb_strtoupper(OVERLAY_TOKEN)],
    'com símbolos' => [str_repeat('-', 32)],
]);
