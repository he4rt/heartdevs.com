<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the register page', function (): void {
    get(route('register'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/register')->has('passwordRules'));
});

it('registers a user and sends the verification mail', function (): void {
    Notification::fake();

    post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@he4rt.dev',
        'password' => 'a-long-password',
        'password_confirmation' => 'a-long-password',
    ])->assertRedirect(route('panel-app::dashboard', absolute: false));

    assertAuthenticated();
    $user = User::query()->where('email', 'ada@he4rt.dev')->sole();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);

    get(route('panel-app::dashboard'))->assertRedirect(route('verification.notice'));
});

it('refuses an email that has an account', function (): void {
    User::factory()->create(['email' => 'ada@he4rt.dev']);

    post(route('register.store'), [
        'name' => 'Ada',
        'email' => 'ada@he4rt.dev',
        'password' => 'a-long-password',
        'password_confirmation' => 'a-long-password',
    ])->assertSessionHasErrors('email');
});
