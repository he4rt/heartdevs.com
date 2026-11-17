<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the login page to a guest', function (): void {
    get(route('panel-admin::login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/login'));
});

it('signs a super admin in and regenerates the session', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $sessionId = session()->getId();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('panel-admin::home', absolute: false))
        ->assertSessionHasNoErrors();

    assertAuthenticatedAs($admin);
    expect(session()->getId())->not->toBe($sessionId);
});

it('enters the admin through a full page visit, because the login page runs in its own bundle', function (): void {
    $admin = User::factory()->superAdmin()->create();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::home'));

    assertAuthenticatedAs($admin);
});

it('returns to the page the guest asked for before the login', function (): void {
    $admin = User::factory()->superAdmin()->create();

    get(route('panel-admin::users.index'))->assertRedirect(route('panel-admin::login'));

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::users.index'));
});

it('refuses a wrong password', function (): void {
    $admin = User::factory()->superAdmin()->create();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    assertGuest();
});

it('refuses a regular user with the same message as a wrong password', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::User);

    post(route('panel-admin::login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    assertGuest();
});

it('locks the form after five failed attempts', function (): void {
    $admin = User::factory()->superAdmin()->create();

    foreach (range(1, 5) as $attempt) {
        post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'wrong']);
    }

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    assertGuest();
    expect(session('errors')->first('email'))->toStartWith('Too many login attempts');
});

it('sends a signed-in super admin from the login page to the dashboard', function (): void {
    actingAs(User::factory()->superAdmin()->create());

    get(route('panel-admin::login'))->assertRedirect(route('panel-admin::home'));
});

it('logs out and leaves through a full page visit', function (): void {
    actingAs(User::factory()->superAdmin()->create());

    post(route('panel-admin::logout'), headers: ['X-Inertia' => 'true'])->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::login'));

    assertGuest();
});

it('asks a super admin with two factor for a code before the admin', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false]);
    $admin = User::factory()->superAdmin()->withTwoFactor()->create();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', route('two-factor.login'));

    assertGuest();

    post(route('two-factor.login.store'), ['code' => resolve(Google2FA::class)->getCurrentOtp('JBSWY3DPEHPK3PXP')], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::home'));

    assertAuthenticatedAs($admin);
});

it('keeps a super admin out with a wrong two factor code', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false]);
    $admin = User::factory()->superAdmin()->withTwoFactor()->create();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password']);
    post(route('two-factor.login.store'), ['code' => '000000'])->assertSessionHasErrors('code');

    assertGuest();
});

it('lets a super admin with two factor in without a code while two factor is off', function (): void {
    $admin = User::factory()->superAdmin()->withTwoFactor()->create();

    post(route('panel-admin::login.store'), ['email' => $admin->email, 'password' => 'password'], ['X-Inertia' => 'true'])
        ->assertConflict()
        ->assertHeader('X-Inertia-Location', route('panel-admin::home'));

    assertAuthenticatedAs($admin);
    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});
