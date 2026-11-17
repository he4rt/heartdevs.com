<?php

declare(strict_types=1);

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the login page', function (): void {
    get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/login')
            ->where('canResetPassword', expected: true)
            ->where('canRegister', expected: true)
            ->where('canUsePasskeys', expected: false));
});

it('offers the passkey login while passkeys are on', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => false, 'passkeysEnabled' => true]);

    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('canUsePasskeys', expected: true));
    get(route('passkey.login-options'))->assertOk();
});

it('hides the passkey routes while passkeys are off', function (): void {
    $this->withExceptionHandling()->get(route('passkey.login-options'))->assertNotFound();
});

it('signs a user in and sends them to the dashboard', function (): void {
    $user = User::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('panel-app::dashboard', absolute: false));

    assertAuthenticated();
    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('refuses a wrong password', function (): void {
    $user = User::factory()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');

    assertGuest();
});

it('asks for the second factor when two factor is on', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false]);
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.id', $user->id);

    assertGuest();
    get(route('two-factor.login'))->assertInertia(fn (AssertableInertia $page) => $page->component('auth/two-factor-challenge'));
});

it('skips the second factor while two factor is off', function (): void {
    $user = User::factory()->withTwoFactor()->create();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('panel-app::dashboard', absolute: false));

    assertAuthenticated();
});

it('hides the two factor routes while two factor is off', function (): void {
    $user = User::factory()->withTwoFactor()->create();
    actingAs($user)->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);

    $this->withExceptionHandling()->get(route('two-factor.qr-code'))->assertNotFound();
    $this->withExceptionHandling()->get(route('two-factor.login'))->assertNotFound();
});

it('signs in with a recovery code', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false]);
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    post(route('two-factor.login.store'), ['recovery_code' => 'code-1'])->assertRedirect(route('panel-app::dashboard', absolute: false));

    assertAuthenticated();
    expect($user->refresh()->recoveryCodes())->not->toContain('code-1');
});

it('signs a user out', function (): void {
    actingAs(User::factory()->create());

    post(route('logout'))->assertRedirect('/');

    assertGuest();
});

it('limits login attempts to five a minute', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    }

    post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertTooManyRequests();
});

it('sends a guest from the dashboard to the login page', function (): void {
    get(route('panel-app::dashboard'))->assertRedirect(route('login'));
});
