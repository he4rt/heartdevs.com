<?php

declare(strict_types=1);

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Laravel\Passkeys\Passkey;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => false, 'passkeysEnabled' => true]);
});

/**
 * @return array<string, int>
 */
function confirmedPasswordSession(): array
{
    return ['auth.password_confirmed_at' => Date::now()->getTimestamp()];
}

it('asks for the password before it shows the security settings', function (): void {
    actingAs(User::factory()->create());

    get(route('panel-app::settings.security.edit'))->assertRedirect(route('password.confirm'));
});

it('renders the security settings with two factor and passkeys', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => true]);
    $user = User::factory()->withTwoFactor()->create();
    Passkey::query()->forceCreate(['user_id' => $user->id, 'name' => 'Chrome on Linux', 'credential_id' => 'abc', 'credential' => '{}']);
    actingAs($user)->withSession(confirmedPasswordSession());

    get(route('panel-app::settings.security.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/security')
            ->where('twoFactorAvailable', expected: true)
            ->where('twoFactorEnabled', expected: true)
            ->where('passkeys.0.name', 'Chrome on Linux')
            ->has('passwordRules'));
});

it('updates the password', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    put(route('panel-app::settings.password.update'), [
        'current_password' => 'password',
        'password' => 'a-new-long-password',
        'password_confirmation' => 'a-new-long-password',
    ])->assertSessionHasNoErrors()->assertInertiaFlash('toast.message', 'Password updated');

    expect(Hash::check('a-new-long-password', $user->refresh()->password))->toBeTrue();
});

it('refuses a wrong current password', function (): void {
    actingAs(User::factory()->create());

    put(route('panel-app::settings.password.update'), [
        'current_password' => 'wrong',
        'password' => 'a-new-long-password',
        'password_confirmation' => 'a-new-long-password',
    ])->assertSessionHasErrors('current_password');
});

it('turns two factor on after the app proves it has the secret', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false]);
    $user = User::factory()->create();
    actingAs($user)->withSession(confirmedPasswordSession());

    post(route('two-factor.enable'))->assertSessionHasNoErrors();
    $secret = decrypt((string) $user->refresh()->two_factor_secret);

    post(route('two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();

    post(route('two-factor.confirm'), ['code' => resolve(Google2FA::class)->getCurrentOtp($secret)])->assertSessionHasNoErrors();
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('deletes a passkey', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::query()->forceCreate(['user_id' => $user->id, 'name' => 'Laptop', 'credential_id' => 'abc', 'credential' => '{}']);
    actingAs($user)->withSession(confirmedPasswordSession());

    delete(route('passkey.destroy', $passkey))->assertSessionHasNoErrors();

    expect(Passkey::query()->find($passkey->id))->toBeNull();
});

it('renders the appearance settings', function (): void {
    actingAs(User::factory()->create());

    get(route('panel-app::settings.appearance.edit'))->assertInertia(fn (AssertableInertia $page) => $page->component('settings/appearance'));
});

it('hides the two factor and passkey sections while both are off', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => false, 'passkeysEnabled' => false]);
    actingAs(User::factory()->create())->withSession(confirmedPasswordSession());

    get(route('panel-app::settings.security.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('twoFactorAvailable', expected: false)
            ->where('passkeysAvailable', expected: false));
});
