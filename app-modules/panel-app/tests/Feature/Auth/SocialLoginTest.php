<?php

declare(strict_types=1);

use He4rt\Identity\SocialAccounts\SocialAccount;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuthUser;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

beforeEach(function (): void {
    foreach (SocialProvider::cases() as $provider) {
        config(['services.'.$provider->value => ['client_id' => 'id', 'client_secret' => 'secret', 'redirect' => '/auth/'.$provider->value.'/callback']]);
    }

    AuthenticationSettings::fake(['twoFactorEnabled' => false, 'passkeysEnabled' => false, 'socialProviders' => ['github', 'google']]);
});

function fakeProviderUser(string $driver, array $attributes = []): void
{
    Socialite::fake($driver, OAuthUser::fake(array_merge(['id' => '583231', 'name' => 'Ana Lima', 'email' => 'ana@example.test'], $attributes)));
}

it('shows a button for each enabled provider', function (): void {
    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page
        ->count('socialProviders', 2)
        ->where('socialProviders.0.value', 'github')
        ->where('socialProviders.1.label', 'Google'));
});

it('shows no button while no provider is on', function (): void {
    AuthenticationSettings::fake(['socialProviders' => []]);

    get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->count('socialProviders', 0));
});

it('sends the guest to the provider', function (): void {
    Socialite::fake('github');

    get(route('panel-app::social-login.redirect', 'github'))->assertRedirect('https://socialite.fake/github/authorize');
});

it('hides a provider that is off or unknown', function (string $provider): void {
    $this->withExceptionHandling()->get('/auth/'.$provider)->assertNotFound();
    $this->withExceptionHandling()->get('/auth/'.$provider.'/callback')->assertNotFound();
})->with(['off' => ['discord'], 'unknown' => ['gitlab']]);

it('signs a new user up and logs them in', function (): void {
    fakeProviderUser('github');

    get(route('panel-app::social-login.callback', 'github'))->assertRedirect(route('panel-app::dashboard', absolute: false));

    $user = User::query()->where('email', 'ana@example.test')->sole();

    assertAuthenticatedAs($user);
    expect($user->hasPassword())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('logs a linked user in', function (): void {
    $account = SocialAccount::factory()->create(['provider_user_id' => '583231']);
    fakeProviderUser('github', ['email' => 'changed@example.test']);

    get(route('panel-app::social-login.callback', 'github'))->assertRedirect();

    assertAuthenticatedAs($account->user);
});

it('asks for the second factor before it logs in', function (): void {
    AuthenticationSettings::fake(['twoFactorEnabled' => true, 'passkeysEnabled' => false, 'socialProviders' => ['github']]);
    $user = User::factory()->withTwoFactor()->create();
    SocialAccount::factory()->for($user)->create(['provider_user_id' => '583231']);
    fakeProviderUser('github');

    get(route('panel-app::social-login.callback', 'github'))
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHas('login.id', $user->id);

    assertGuest();
});

it('trusts only the email that Google verified', function (bool $verified): void {
    User::factory()->create(['email' => 'ana@example.test']);
    fakeProviderUser('google', ['email_verified' => $verified]);

    $response = get(route('panel-app::social-login.callback', 'google'));

    if ($verified) {
        $response->assertRedirect(route('panel-app::dashboard', absolute: false));
        assertAuthenticatedAs(User::query()->where('email', 'ana@example.test')->sole());
    } else {
        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['social' => 'An account with this email already exists. Log in and connect Google in Settings.']);
        assertGuest();
    }
})->with(['verified' => [true], 'not verified' => [false]]);

it('trusts only the email that Discord verified', function (): void {
    AuthenticationSettings::fake(['socialProviders' => ['discord']]);
    User::factory()->create(['email' => 'ana@example.test']);
    fakeProviderUser('discord', ['verified' => false]);

    get(route('panel-app::social-login.callback', 'discord'))
        ->assertSessionHasErrors(['social' => 'An account with this email already exists. Log in and connect Discord in Settings.']);

    expect(SocialAccount::query()->exists())->toBeFalse();
});

it('creates nobody while registration is closed', function (): void {
    config(['fortify.features' => array_values(array_diff(config('fortify.features'), ['registration']))]);
    fakeProviderUser('github');

    get(route('panel-app::social-login.callback', 'github'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['social' => 'No account uses this email, and sign up is closed.']);

    expect(User::query()->exists())->toBeFalse();
});

it('explains a failed sign in at the provider', function (): void {
    Socialite::fake('github', fn () => throw new InvalidStateException);

    get(route('panel-app::social-login.callback', 'github'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['social' => 'Could not log in with GitHub. Try again.']);

    assertGuest();
});

it('sends a logged in user away from the guest routes', function (): void {
    actingAs(User::factory()->create());

    get(route('panel-app::social-login.redirect', 'github'))->assertRedirect(route('panel-app::dashboard', absolute: false));
});
