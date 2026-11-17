<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('sends a reset link and resets the password with it', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    get(route('password.request'))->assertInertia(fn (AssertableInertia $page) => $page->component('auth/forgot-password'));
    post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/reset-password')
                ->where('email', $user->email)
                ->where('token', $notification->token));

        post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'a-new-long-password',
            'password_confirmation' => 'a-new-long-password',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect(Hash::check('a-new-long-password', $user->refresh()->password))->toBeTrue();
});
