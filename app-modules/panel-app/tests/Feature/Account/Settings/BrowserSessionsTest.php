<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    config(['session.driver' => 'database']);
});

function insertOtherSession(User $user): void
{
    DB::table(config('session.table'))->insert([
        'id' => 'other-session',
        'user_id' => $user->id,
        'ip_address' => '10.0.0.9',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Mac OS X) Firefox/130.0',
        'payload' => '',
        'last_activity' => Date::now()->subMinutes(30)->getTimestamp(),
    ]);
}

function otherSessionExists(): bool
{
    return DB::table(config('session.table'))->where('id', 'other-session')->exists();
}

it('lists the browser sessions on the profile', function (): void {
    $user = User::factory()->create();
    insertOtherSession($user);

    actingAs($user)->get(route('panel-app::settings.profile.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.browser', 'Firefox')
            ->where('sessions.0.isCurrent', expected: false));
});

it('logs out the other sessions with the password', function (): void {
    $user = User::factory()->create();
    insertOtherSession($user);

    actingAs($user)->delete(route('panel-app::settings.sessions.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Other sessions logged out');

    expect(otherSessionExists())->toBeFalse();
});

it('keeps the other sessions with a wrong password', function (): void {
    $user = User::factory()->create();
    insertOtherSession($user);

    actingAs($user);
    $this->withExceptionHandling()
        ->delete(route('panel-app::settings.sessions.destroy'), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');

    expect(otherSessionExists())->toBeTrue();
});

it('logs out the other sessions of a user without a password after a recent confirmation', function (): void {
    $user = User::factory()->withoutPassword()->create();
    insertOtherSession($user);

    actingAs($user)->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()])
        ->delete(route('panel-app::settings.sessions.destroy'))
        ->assertSessionHasNoErrors();

    expect(otherSessionExists())->toBeFalse();
});

it('keeps the other sessions of a user without a password and without a recent confirmation', function (): void {
    $user = User::factory()->withoutPassword()->create();
    insertOtherSession($user);

    actingAs($user);
    $this->withExceptionHandling()->delete(route('panel-app::settings.sessions.destroy'))->assertForbidden();

    expect(otherSessionExists())->toBeTrue();
});
