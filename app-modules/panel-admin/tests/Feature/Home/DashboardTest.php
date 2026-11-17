<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows counts and the shared layout props', function (): void {
    $admin = User::factory()->superAdmin()->create(['name' => 'Ada Lovelace']);
    User::factory()->count(2)->create();

    actingAs($admin);

    get(route('panel-admin::home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home/dashboard')
            ->where('stats.0', ['key' => 'users', 'label' => 'Users', 'value' => 3])
            ->where('app.user.email', $admin->email)
            ->where('app.user.initials', 'AL')
            ->where('app.navigation.0.items.0.key', 'dashboard')
            ->where('app.navigation.0.items.0.current', expected: true));
});
