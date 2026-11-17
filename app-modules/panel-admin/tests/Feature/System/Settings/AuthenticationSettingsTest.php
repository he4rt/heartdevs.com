<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\SocialAccounts\SocialProvider;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(function (): void {
    config([
        'services.github.client_id' => 'github-id',
        'services.github.client_secret' => 'github-secret',
        'services.google.client_id' => null,
        'services.discord.client_id' => null,
        'services.twitch.client_id' => null,
    ]);
    $this->admin = User::factory()->superAdmin()->create();

    actingAs($this->admin);
});

function revokeSettingsPermission(User $admin, PermissionsEnum $action): void
{
    $admin->roles()->first()?->revokePermissionTo($action->buildPermissionFor(AuthenticationSettings::class));
    $admin->forgetCachedPermissions();
}

it('opens the first tab the user may view', function (): void {
    get(route('panel-admin::settings.index'))->assertRedirect(route('panel-admin::settings.authentication.edit', absolute: false));
});

it('renders the authentication tab', function (): void {
    get(route('panel-admin::settings.authentication.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/authentication')
            ->where('tabs.0.key', 'authentication')
            ->where('tabs.0.current', expected: true)
            ->where('settings.twoFactorEnabled', expected: false)
            ->where('settings.passkeysEnabled', expected: false)
            ->where('canUpdate', expected: true));
});

it('saves the authentication methods and logs the change', function (): void {
    put(route('panel-admin::settings.authentication.update'), ['twoFactorEnabled' => true, 'passkeysEnabled' => true, 'socialProviders' => []])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Settings saved');

    $settings = resolve(AuthenticationSettings::class);
    $activity = Activity::query()->where('event', 'settings_updated')->sole();

    expect($settings->twoFactorEnabled)->toBeTrue()
        ->and($settings->passkeysEnabled)->toBeTrue()
        ->and($activity->causer_id)->toBe($this->admin->id)
        ->and(data_get($activity->properties, 'old.twoFactorEnabled'))->toBeFalse()
        ->and(data_get($activity->properties, 'attributes.twoFactorEnabled'))->toBeTrue();
});

it('refuses a value that is not a boolean', function (): void {
    $this->withExceptionHandling()
        ->put(route('panel-admin::settings.authentication.update'), ['twoFactorEnabled' => 'maybe'])
        ->assertSessionHasErrors(['twoFactorEnabled', 'passkeysEnabled']);
});

it('shows the tab read only without the update permission', function (): void {
    revokeSettingsPermission($this->admin, PermissionsEnum::Update);

    get(route('panel-admin::settings.authentication.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canUpdate', expected: false));

    $this->withExceptionHandling()
        ->put(route('panel-admin::settings.authentication.update'), ['twoFactorEnabled' => true, 'passkeysEnabled' => true, 'socialProviders' => []])
        ->assertForbidden();

    expect(resolve(AuthenticationSettings::class)->twoFactorEnabled)->toBeFalse();
});

it('hides the settings without the view permission', function (): void {
    revokeSettingsPermission($this->admin, PermissionsEnum::View);

    $this->withExceptionHandling()->get(route('panel-admin::settings.authentication.edit'))->assertForbidden();
    $this->withExceptionHandling()->get(route('panel-admin::settings.index'))->assertForbidden();
});

it('lists every provider with its credentials state', function (): void {
    get(route('panel-admin::settings.authentication.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settings.socialProviders', [])
            ->count('socialProviders', 4)
            ->where('socialProviders.0.value', 'github')
            ->where('socialProviders.0.configured', expected: true)
            ->where('socialProviders.2.value', 'discord')
            ->where('socialProviders.2.configured', expected: false)
            ->where('socialProviders.2.credentialKeys', 'DISCORD_CLIENT_ID and DISCORD_CLIENT_SECRET')
            ->where('socialProviders.2.callbackUrl', url('/auth/discord/callback')));
});

it('turns a configured provider on', function (): void {
    put(route('panel-admin::settings.authentication.update'), ['twoFactorEnabled' => false, 'passkeysEnabled' => false, 'socialProviders' => ['github']])
        ->assertSessionHasNoErrors();

    expect(resolve(AuthenticationSettings::class)->socialProviders)->toBe(['github']);

    get(route('panel-admin::settings.authentication.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.socialProviders', ['github']));
});

it('refuses a provider without credentials and changes nothing', function (string $value): void {
    $this->withExceptionHandling()
        ->put(route('panel-admin::settings.authentication.update'), ['twoFactorEnabled' => true, 'passkeysEnabled' => true, 'socialProviders' => [$value]])
        ->assertSessionHasErrors('socialProviders.0');

    expect(resolve(AuthenticationSettings::class))
        ->twoFactorEnabled->toBeFalse()
        ->socialProviders->toBeEmpty();
})->with(['without credentials' => [SocialProvider::Discord->value], 'not a provider' => ['gitlab']]);

it('keeps a provider whose credentials were removed out of the form', function (): void {
    AuthenticationSettings::fake(['socialProviders' => ['github', 'discord']]);

    get(route('panel-admin::settings.authentication.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.socialProviders', ['github']));
});
