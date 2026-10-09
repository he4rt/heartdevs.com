<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Pages\Login;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

test('admin login hides email/password outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this
        ->get('/admin/login')
        ->assertOk()
        ->assertSee('lastAuthProvider')
        ->assertDontSee('data.email');
});

test('admin login shows email/password in local', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $this
        ->get('/admin/login')
        ->assertOk()
        ->assertSee('data.email');
});

test('admin credential authenticate is unavailable outside local', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    (new Login)->authenticate();
})->throws(NotFoundHttpException::class);
