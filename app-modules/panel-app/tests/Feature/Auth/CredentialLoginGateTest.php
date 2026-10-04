<?php

declare(strict_types=1);

use He4rt\PanelApp\Pages\LoginPage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('app login hides email/password outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this
        ->get('/app/login')
        ->assertOk()
        ->assertSee('lastAuthProvider')
        ->assertDontSee('data.email');
});

test('app login shows email/password in local', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $this
        ->get('/app/login')
        ->assertOk()
        ->assertSee('data.email');
});

test('app credential authenticate is unavailable outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    (new LoginPage)->authenticate();
})->throws(NotFoundHttpException::class);
