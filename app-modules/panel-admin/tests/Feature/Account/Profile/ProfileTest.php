<?php

declare(strict_types=1);

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use He4rt\PanelAdmin\Shared\Http\Middleware\HandleAdminInertiaRequests;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Laravel\Passkeys\Passkey;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create([
        'email' => 'admin@he4rt.dev',
        'password' => 'old-password',
        'locale' => 'en',
        'theme_color' => null,
    ]);

    actingAs($this->admin)->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);
});

it('shows the profile with the languages', function (): void {
    get(route('panel-admin::profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('profile/edit')
            ->where('profile.email', 'admin@he4rt.dev')
            ->where('profile.locale', 'en')
            ->has('languages', 2)
            ->has('sessions'));
});

it('updates the name and a lowercased email', function (): void {
    put(route('panel-admin::profile.update'), ['name' => 'Daniel', 'email' => 'Daniel@He4rt.dev'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Profile updated');

    expect($this->admin->refresh())
        ->name->toBe('Daniel')
        ->email->toBe('daniel@he4rt.dev');
});

it('refuses an email that another user has', function (): void {
    User::factory()->create(['email' => 'taken@he4rt.dev']);

    $this->withExceptionHandling()
        ->put(route('panel-admin::profile.update'), ['name' => 'Daniel', 'email' => 'taken@he4rt.dev'])
        ->assertSessionHasErrors('email');
});

it('changes the password with the current one', function (): void {
    put(route('panel-admin::profile.password'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('new-password-123', $this->admin->refresh()->password))->toBeTrue();
});

it('refuses a wrong current password', function (): void {
    $this->withExceptionHandling()
        ->put(route('panel-admin::profile.password'), [
            'current_password' => 'wrong',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password', $this->admin->refresh()->password))->toBeTrue();
});

it('saves preferences, switches the language and shares the accent color', function (): void {
    put(route('panel-admin::profile.preferences'), ['locale' => 'pt_BR', 'theme_color' => '#22C55E'])
        ->assertSessionHasNoErrors();

    get(route('panel-admin::profile.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('app.user.themeColor', '#22c55e')
            ->where('app.user.locale', 'pt_BR'));

    expect(app()->getLocale())->toBe('pt_BR');
});

it('clears the accent color and refuses bad preferences', function (): void {
    $this->admin->update(['theme_color' => '#22c55e']);

    put(route('panel-admin::profile.preferences'), ['locale' => 'en', 'theme_color' => ''])->assertSessionHasNoErrors();
    expect($this->admin->refresh()->theme_color)->toBeNull();

    $this->withExceptionHandling()
        ->put(route('panel-admin::profile.preferences'), ['locale' => 'fr', 'theme_color' => 'red'])
        ->assertSessionHasErrors(['locale', 'theme_color']);
});

it('lists the browser sessions and logs out the others', function (): void {
    config(['session.driver' => 'database']);
    $this->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);
    $table = config('session.table');
    DB::table($table)->insert([
        ['id' => 'other-session', 'user_id' => $this->admin->id, 'ip_address' => '10.0.0.9', 'user_agent' => 'Mozilla/5.0 (Macintosh; Mac OS X) Firefox/130.0', 'payload' => '', 'last_activity' => now()->subMinutes(30)->timestamp],
        ['id' => 'foreign-session', 'user_id' => User::factory()->create()->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'curl', 'payload' => '', 'last_activity' => now()->timestamp],
    ]);

    get(route('panel-admin::profile.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.browser', 'Firefox')
            ->where('sessions.0.platform', 'macOS')
            ->where('sessions.0.isCurrent', expected: false));

    $this->delete(route('panel-admin::profile.sessions.destroy'), ['password' => 'old-password'])->assertRedirect();

    expect(DB::table($table)->where('id', 'other-session')->exists())->toBeFalse()
        ->and(DB::table($table)->where('id', 'foreign-session')->exists())->toBeTrue();
});

it('asks for the right password before it logs out the other sessions', function (): void {
    $this->withExceptionHandling()
        ->delete(route('panel-admin::profile.sessions.destroy'), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');

    $this->delete(route('panel-admin::profile.sessions.destroy'), ['password' => 'old-password'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Other sessions signed out');
});

it('asks for the password before it shows the profile', function (): void {
    $this->flushSession();

    get(route('panel-admin::profile.edit'))->assertRedirect(route('password.confirm'));
});

it('shows two factor and the passkeys when the methods are on', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => true]);
    $this->admin->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
    Passkey::query()->forceCreate(['user_id' => $this->admin->id, 'name' => 'Chrome on Linux', 'credential_id' => 'abc', 'credential' => '{}']);

    get(route('panel-admin::profile.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('twoFactorAvailable', expected: true)
            ->where('twoFactorEnabled', expected: true)
            ->where('passkeysAvailable', expected: true)
            ->where('passkeys.0.name', 'Chrome on Linux')
            ->where('profile.hasPassword', expected: true));
});

it('sends a visit that ends on an app page to a full reload', function (): void {
    $this->flushSession();

    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => resolve(HandleAdminInertiaRequests::class)->version(request())])
        ->get(route('password.confirm'))
        ->assertConflict();
});
