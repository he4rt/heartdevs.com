<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('responde 200 na home', function (): void {
    get('/')->assertOk();
});

it('exibe o logo He4rt animado no hero', function (): void {
    get('/')
        ->assertOk()
        ->assertSee('he4rt-logo', false)
        ->assertSee('class="led', false);
});
