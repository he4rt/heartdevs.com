<?php

declare(strict_types=1);

use He4rt\Identity\Users\Actions\StartImpersonation;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function turnTwoFactor(bool $enabled): void
{
    AuthenticationSettings::fake(['twoFactorEnabled' => $enabled, 'passkeysEnabled' => false]);
    session(['auth.password_confirmed_at' => now()->getTimestamp()]);
}

it('sends a user without two factor to the setup', function (): void {
    turnTwoFactor(enabled: true);
    actingAs(User::factory()->create());

    get(route('panel-app::dashboard'))->assertRedirect(route('panel-app::two-factor.setup'));
});

it('sends a super admin without two factor from the admin to the setup', function (): void {
    turnTwoFactor(enabled: true);
    actingAs(User::factory()->superAdmin()->create());

    get(route('panel-admin::users.index'))->assertRedirect(route('panel-app::two-factor.setup'));
});

it('renders the setup page', function (): void {
    turnTwoFactor(enabled: true);
    actingAs(User::factory()->create());

    get(route('panel-app::two-factor.setup'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('two-factor-setup/index'));
});

it('returns to the page the user asked for once two factor is on', function (): void {
    turnTwoFactor(enabled: true);
    $admin = User::factory()->superAdmin()->create();
    actingAs($admin);

    get(route('panel-admin::users.index'));
    $admin->forceFill(User::factory()->withTwoFactor()->raw())->save();

    get(route('panel-app::two-factor.setup'))->assertRedirect(route('panel-admin::users.index'));
});

it('sends a user who has two factor from the setup to the dashboard', function (): void {
    turnTwoFactor(enabled: true);
    actingAs(User::factory()->withTwoFactor()->create());

    get(route('panel-app::two-factor.setup'))->assertRedirect(route('panel-app::dashboard'));
});

it('lets a user without two factor in while two factor is off', function (): void {
    turnTwoFactor(enabled: false);
    actingAs(User::factory()->create());

    get(route('panel-app::dashboard'))->assertOk();
});

it('does not ask a super admin who impersonates a user', function (): void {
    turnTwoFactor(enabled: true);
    $admin = User::factory()->superAdmin()->withTwoFactor()->create();
    $user = User::factory()->create();
    actingAs($admin);
    resolve(StartImpersonation::class)->handle($admin, $user, returnTo: '/admin/users');

    get(route('panel-app::dashboard'))->assertOk();
});
