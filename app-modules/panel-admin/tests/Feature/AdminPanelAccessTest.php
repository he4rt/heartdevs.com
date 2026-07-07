<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;

test('unauthenticated user is redirected to login', function (): void {
    $this
        ->get('/admin')
        ->assertRedirect('/admin/login');
});

test('admin login page renders', function (): void {
    $this
        ->get('/admin/login')
        ->assertOk();
});

test('authenticated admin can access admin panel', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    config(['he4rt.admins' => 'danielhe4rt']);

    $this
        ->actingAs($user)
        ->get('/admin')
        ->assertRedirect();
});

test('admin user can access panel via canAccessPanel', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    config(['he4rt.admins' => 'danielhe4rt']);

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('non-admin user cannot access admin panel in production', function (): void {
    $user = User::factory()->create(['username' => 'regular-user']);

    config(['he4rt.admins' => 'danielhe4rt']);

    app()->detectEnvironment(fn () => 'production');

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});
