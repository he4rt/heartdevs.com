<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    config(['he4rt.impersonation.redirect_to' => '/app']);

    $this->admin = User::factory()->superAdmin()->create(['name' => 'Admin']);
    $this->ada = User::factory()->create(['name' => 'Ada']);

    actingAs($this->admin);
});

it('offers impersonation only for users it may switch to', function (): void {
    $otherAdmin = User::factory()->superAdmin()->create(['name' => 'Bea']);

    get(route('panel-admin::users.index', ['sort' => 'name']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('users.data.0.name', 'Ada')
            ->where('users.data.0.canImpersonate', expected: true)
            ->where('users.data.1.name', 'Admin')
            ->where('users.data.1.canImpersonate', expected: false)
            ->where('users.data.2.name', $otherAdmin->name)
            ->where('users.data.2.canImpersonate', expected: false));
});

it('hides impersonation while no app receives the user', function (): void {
    config(['he4rt.impersonation.redirect_to' => null]);

    get(route('panel-admin::users.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('users.data.0.canImpersonate', expected: false));

    $this->withExceptionHandling()
        ->post(route('panel-admin::users.impersonate', $this->ada))
        ->assertNotFound();
});

it('signs in as the user, logs it and returns to the admin on leave', function (): void {
    post(route('panel-admin::users.impersonate', $this->ada), [], ['X-Inertia' => 'true'])->assertConflict()
        ->assertHeader('X-Inertia-Location', '/app');

    expect(auth()->user()->is($this->ada))->toBeTrue()
        ->and(Activity::query()->where('event', 'impersonation_started')->where('subject_id', $this->ada->id)->sole()->causer_id)->toBe($this->admin->id);

    post(route('identity.impersonation.leave'))
        ->assertRedirect(route('panel-admin::users.index'));

    expect(auth()->user()->is($this->admin))->toBeTrue()
        ->and(Activity::query()->where('event', 'impersonation_ended')->sole()->causer_id)->toBe($this->admin->id);

    get(route('panel-admin::users.index'))->assertOk();
});

it('refuses a super admin target with an error toast', function (): void {
    $otherAdmin = User::factory()->superAdmin()->create();

    post(route('panel-admin::users.impersonate', $otherAdmin))
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'You cannot impersonate another super admin.');

    expect(auth()->user()->is($this->admin))->toBeTrue();
});

it('refuses to leave when there is no impersonation', function (): void {
    $this->withExceptionHandling()
        ->post(route('identity.impersonation.leave'))
        ->assertForbidden();
});

it('shows who started the impersonation in the panel-app', function (): void {
    post(route('panel-admin::users.impersonate', $this->ada), [], ['X-Inertia' => 'true'])->assertConflict();

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('account.impersonatorName', 'Admin'));
});

it('hides the impersonation banner in a normal session', function (): void {
    actingAs($this->ada);

    get(route('panel-app::dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('account.impersonatorName', expected: null));
});
