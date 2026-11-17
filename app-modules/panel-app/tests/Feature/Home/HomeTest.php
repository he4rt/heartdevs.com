<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the welcome page to a guest', function (): void {
    get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/welcome')
            ->where('account', expected: null)
            ->where('dashboardUrl', '/dashboard')
            ->where('loginUrl', '/login')
            ->where('registerUrl', '/register'));
});

it('shows the welcome page with the account to a signed-in user', function (): void {
    actingAs(User::factory()->create(['name' => 'Ada Lovelace']));

    get('/')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('account.user.name', 'Ada Lovelace')
        ->where('account.user.initials', 'AL'));
});

it('shows the dashboard with the navigation to a signed-in user', function (): void {
    actingAs(User::factory()->create());

    get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/dashboard')
            ->where('account.navigation.0.items.0.key', 'dashboard')
            ->where('account.navigation.0.items.0.current', expected: true));
});

it('links to the admin only for a user who can open it', function (bool $isSuperAdmin): void {
    $factory = User::factory();
    actingAs(($isSuperAdmin ? $factory->superAdmin() : $factory)->create());

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('account.canAccessAdmin', $isSuperAdmin));
})->with(['super admin' => [true], 'user' => [false]]);
