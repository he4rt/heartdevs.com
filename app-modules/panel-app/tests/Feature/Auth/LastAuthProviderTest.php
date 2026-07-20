<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Pages\TimelinePage;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

test('login reads the last provider without loading the writer asset', function (): void {
    $this->get(Filament::getPanel('app')->getLoginUrl())
        ->assertSuccessful()
        ->assertSee("window.localStorage.getItem('lastAuthProvider')", escape: false)
        ->assertDontSee('last-auth-provider', escape: false);
});

test('oauth callback landing loads the writer asset for a supported provider', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(url()->query(TimelinePage::getUrl(), [
        'oauth_provider' => 'github',
    ]))
        ->assertSuccessful()
        ->assertSee('last-auth-provider', escape: false);
});

test('dashboard does not load the writer asset without a supported oauth marker', function (array $query): void {
    $this->actingAs(User::factory()->create());

    $this->get(url()->query(TimelinePage::getUrl(), $query))
        ->assertSuccessful()
        ->assertDontSee('last-auth-provider', escape: false);
})->with([
    'without marker' => [[]],
    'unsupported provider' => [['oauth_provider' => 'devto']],
]);
