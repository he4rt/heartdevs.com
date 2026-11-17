<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as OAuthUser;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    foreach (SocialProvider::cases() as $provider) {
        config(['services.'.$provider->value => ['client_id' => 'id', 'client_secret' => 'secret', 'redirect' => '/auth/'.$provider->value.'/callback']]);
    }

    AuthenticationSettings::fake(['twoFactorEnabled' => false, 'passkeysEnabled' => false, 'socialProviders' => ['github', 'google']]);
});

/**
 * @return array<string, int>
 */
function confirmedSession(): array
{
    return ['auth.password_confirmed_at' => Date::now()->getTimestamp()];
}

function fakeGitHubUser(string $id = '583231'): void
{
    Socialite::fake('github', OAuthUser::fake(['id' => $id, 'name' => 'Ana Lima', 'email' => 'ana@example.test']));
}

it('lists the enabled providers and the connected accounts', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->for($user)->create();
    SocialAccount::factory()->for($user)->provider(SocialProvider::Twitch)->create();

    actingAs($user)->withSession(confirmedSession())
        ->get(route('panel-app::settings.security.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasPassword', expected: true)
            ->count('socialConnections', 3)
            ->where('socialConnections.0.provider.value', 'github')
            ->where('socialConnections.0.canDisconnect', expected: true)
            ->where('socialConnections.1.provider.value', 'google')
            ->where('socialConnections.1.accountId', expected: null)
            ->where('socialConnections.2.provider.value', 'twitch'));
});

it('connects a provider account', function (): void {
    $user = User::factory()->create();
    fakeGitHubUser();

    actingAs($user)->get(route('panel-app::social-accounts.callback', 'github'))
        ->assertRedirect(route('panel-app::settings.security.edit'))
        ->assertInertiaFlash('toast.message', 'GitHub connected');

    expect($user->ownsSocialAccount(SocialProvider::GitHub, '583231'))->toBeTrue();
});

it('refuses an account that another user owns', function (): void {
    SocialAccount::factory()->create(['provider_user_id' => '583231']);
    $user = User::factory()->create();
    fakeGitHubUser();

    actingAs($user)->get(route('panel-app::social-accounts.callback', 'github'))
        ->assertInertiaFlash('toast.message', 'This GitHub account is linked to another user.');

    expect($user->socialAccounts()->exists())->toBeFalse();
});

it('refuses to connect a provider that is off or already connected', function (): void {
    $user = User::factory()->create();
    SocialAccount::factory()->for($user)->create();
    actingAs($user);

    $this->withExceptionHandling()->get(route('panel-app::social-accounts.redirect', 'discord'))->assertNotFound();
    $this->withExceptionHandling()->get(route('panel-app::social-accounts.redirect', 'github'))->assertForbidden();
});

it('disconnects an account while another way to log in is left', function (): void {
    $account = SocialAccount::factory()->create();

    actingAs($account->user)->delete(route('panel-app::settings.social-accounts.destroy', $account))
        ->assertInertiaFlash('toast.message', 'GitHub disconnected');

    expect(SocialAccount::query()->exists())->toBeFalse();
});

it('keeps the last way to log in', function (): void {
    $account = SocialAccount::factory()->for(User::factory()->withoutPassword())->create();

    actingAs($account->user)->delete(route('panel-app::settings.social-accounts.destroy', $account))
        ->assertInertiaFlash('toast.type', 'error');

    expect(SocialAccount::query()->whereKey($account->id)->exists())->toBeTrue();
});

it('does not find the social account of another user', function (): void {
    $account = SocialAccount::factory()->create();

    actingAs(User::factory()->create());
    $this->withExceptionHandling()->delete(route('panel-app::settings.social-accounts.destroy', $account))->assertNotFound();

    expect(SocialAccount::query()->whereKey($account->id)->exists())->toBeTrue();
});

it('sets the first password of a user who signed up with a provider', function (): void {
    $user = User::factory()->withoutPassword()->create();

    actingAs($user)->post(route('panel-app::settings.password.store'), ['password' => 'N3w!Passw0rd#2026', 'password_confirmation' => 'N3w!Passw0rd#2026'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Password set');

    expect(Hash::check('N3w!Passw0rd#2026', (string) $user->refresh()->password))->toBeTrue();
});

it('refuses to set a password over an existing one', function (): void {
    $user = User::factory()->create();

    actingAs($user);
    $this->withExceptionHandling()
        ->post(route('panel-app::settings.password.store'), ['password' => 'N3w!Passw0rd#2026', 'password_confirmation' => 'N3w!Passw0rd#2026'])
        ->assertForbidden();

    expect(Hash::check('password', (string) $user->refresh()->password))->toBeTrue();
});

it('offers the connected providers on the confirmation page of a user without a password', function (): void {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::factory()->for($user)->create();

    actingAs($user)->get(route('password.confirm'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/confirm-password')
            ->where('hasPassword', expected: false)
            ->count('socialProviders', 1)
            ->where('socialProviders.0.value', 'github'));
});

it('confirms the password with the user own provider account', function (): void {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::factory()->for($user)->create(['provider_user_id' => '583231']);
    fakeGitHubUser();

    actingAs($user)->get(route('panel-app::social-confirmation.callback', 'github'))
        ->assertRedirect(route('panel-app::settings.profile.edit'));

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('does not confirm with a provider account of someone else', function (): void {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::factory()->for($user)->create(['provider_user_id' => '583231']);
    fakeGitHubUser('999');

    actingAs($user)->get(route('panel-app::social-confirmation.callback', 'github'))
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors(['social' => 'This GitHub account is not linked to you.']);

    expect(session('auth.password_confirmed_at'))->toBeNull();
});

it('deletes an account without a password after a recent confirmation', function (): void {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::factory()->for($user)->create();

    actingAs($user)->withSession(confirmedSession())
        ->delete(route('panel-app::settings.profile.destroy'))
        ->assertRedirect(route('panel-app::welcome'));

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(SocialAccount::query()->exists())->toBeFalse();
});

it('keeps an account without a password and without a recent confirmation', function (): void {
    $user = User::factory()->withoutPassword()->create();

    actingAs($user)->get(route('panel-app::settings.profile.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasPassword', expected: false)
            ->where('confirmedRecently', expected: false));

    $this->withExceptionHandling()->delete(route('panel-app::settings.profile.destroy'))->assertForbidden();

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});
