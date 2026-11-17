<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(function (): void {
    actingAs(User::factory()->create(['locale' => 'en', 'theme_color' => null]));
});

it('renders the preferences with the languages', function (): void {
    get(route('panel-app::settings.preferences.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/preferences')
            ->where('locale', 'en')
            ->where('themeColor', expected: null)
            ->has('languages', 2));
});

it('saves the preferences, switches the language and shares the accent color', function (): void {
    put(route('panel-app::settings.preferences.update'), ['locale' => 'pt_BR', 'theme_color' => '#22C55E'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Preferences saved');

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('account.user.themeColor', '#22c55e'));

    expect(app()->getLocale())->toBe('pt_BR');
});

it('refuses a language or a color it does not know', function (): void {
    $this->withExceptionHandling()
        ->put(route('panel-app::settings.preferences.update'), ['locale' => 'fr', 'theme_color' => 'red'])
        ->assertSessionHasErrors(['locale', 'theme_color']);
});
