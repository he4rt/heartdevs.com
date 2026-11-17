<?php

declare(strict_types=1);

use App\Enums\ToastType;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

it('renders the profile settings', function (): void {
    actingAs(User::factory()->create());

    get(route('panel-app::settings.profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/profile')->where('status', expected: null));
});

it('keeps the email verified when only the name changes', function (): void {
    $user = User::factory()->create(['email' => 'ada@he4rt.dev']);
    actingAs($user);

    patch(route('panel-app::settings.profile.update'), ['name' => 'Ada Lovelace', 'email' => 'ada@he4rt.dev'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Profile saved');

    expect($user->refresh())
        ->name->toBe('Ada Lovelace')
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('asks to verify a new email', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    actingAs($user);

    patch(route('panel-app::settings.profile.update'), ['name' => $user->name, 'email' => 'new@he4rt.dev'])->assertSessionHasNoErrors();

    expect($user->refresh())
        ->email->toBe('new@he4rt.dev')
        ->and($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('refuses an email another account has', function (): void {
    User::factory()->create(['email' => 'taken@he4rt.dev']);
    $user = User::factory()->create();
    actingAs($user);

    patch(route('panel-app::settings.profile.update'), ['name' => $user->name, 'email' => 'taken@he4rt.dev'])
        ->assertSessionHasErrors('email');
});

it('deletes the account and signs out', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user);
    actingAs($user);

    delete(route('panel-app::settings.profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('panel-app::welcome'));

    assertGuest();
    expect(User::query()->find($user->id))->toBeNull()
        ->and($team->members()->count())->toBe(0);
});

it('keeps the account with a wrong password', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    delete(route('panel-app::settings.profile.destroy'), ['password' => 'wrong'])->assertSessionHasErrors('password');

    expect(User::query()->find($user->id))->not->toBeNull();
});

it('keeps the account of a team owner', function (): void {
    $user = User::factory()->create();
    Team::factory()->create(['owner_id' => $user->id]);
    actingAs($user);

    delete(route('panel-app::settings.profile.destroy'), ['password' => 'password'])
        ->assertInertiaFlash('toast.type', ToastType::Error->value);

    expect(User::query()->find($user->id))->not->toBeNull();
});
