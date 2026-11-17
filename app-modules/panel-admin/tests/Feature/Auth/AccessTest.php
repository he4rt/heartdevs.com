<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Shared\Http\Middleware\HandleAdminInertiaRequests;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('sends a guest to the login page', function (): void {
    get(route('panel-admin::home'))->assertRedirect(route('panel-admin::login'));
});

it('sends a guest Inertia visit to the login page with a full reload', function (): void {
    $version = (string) resolve(HandleAdminInertiaRequests::class)->version(Request::create('/'));

    get(route('panel-admin::home'), ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::login'));
});

it('hides the admin from a signed-in regular user', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::User);

    actingAs($user);

    get(route('panel-admin::home'))->assertNotFound();
});

it('serves the admin under the configured path', function (): void {
    expect(route('panel-admin::home', absolute: false))->toBe('/admin');
});
