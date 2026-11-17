<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the confirm password page', function (): void {
    actingAs(User::factory()->create());

    get(route('password.confirm'))->assertInertia(fn (AssertableInertia $page) => $page->component('auth/confirm-password'));
});

it('confirms the password', function (): void {
    actingAs(User::factory()->create());

    post(route('password.confirm.store'), ['password' => 'password'])->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('refuses a wrong password', function (): void {
    actingAs(User::factory()->create());

    post(route('password.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('password');
});
