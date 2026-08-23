<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use Spatie\Permission\Models\Role;

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

test('a user holding any role can access the admin panel', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::create(['name' => 'github:viewer', 'guard_name' => 'web']));

    $this
        ->actingAs($user)
        ->get('/admin')
        ->assertOk();
});

test('a user without any role cannot access the admin panel', function (): void {
    $user = User::factory()->create();

    $panel = Filament::getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('the role gate applies outside production too', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

test('a username alone no longer grants access', function (): void {
    $user = User::factory()->create(['username' => 'danielhe4rt']);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});
