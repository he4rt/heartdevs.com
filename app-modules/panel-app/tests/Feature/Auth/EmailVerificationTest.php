<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the verification notice to an unverified user', function (): void {
    actingAs(User::factory()->unverified()->create());

    get(route('verification.notice'))->assertInertia(fn (AssertableInertia $page) => $page->component('auth/verify-email'));
});

it('verifies the email through the signed link', function (): void {
    $user = User::factory()->unverified()->create();
    actingAs($user);

    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    get($link)->assertRedirect(route('panel-app::dashboard', absolute: false).'?verified=1');
    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('sends a new verification link', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    actingAs($user);

    post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});
