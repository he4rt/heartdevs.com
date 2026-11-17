<?php

declare(strict_types=1);

use App\Enums\ToastType;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create(['name' => 'Admin']);

    actingAs($this->admin);
});

it('lists users with the page envelope', function (): void {
    User::factory()->count(30)->create();

    get(route('panel-admin::users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/index')
            ->has('users.data', 25)
            ->where('users.meta.total', 31)
            ->where('users.meta.lastPage', 2)
            ->where('can.create', expected: true)
            ->where('app.navigation.1.items.0.current', expected: true));
});

it('searches users by name or email, case-insensitive', function (): void {
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@he4rt.dev']);
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@he4rt.dev']);

    get(route('panel-admin::users.index', ['filter' => ['search' => 'LOVELACE']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Ada Lovelace'));

    get(route('panel-admin::users.index', ['filter' => ['search' => 'grace@']]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('users.data.0.name', 'Grace Hopper'));
});

it('sorts users by an allowed column', function (): void {
    User::factory()->create(['name' => 'Zed']);
    User::factory()->create(['name' => 'Bea']);

    get(route('panel-admin::users.index', ['sort' => '-name']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('users.data.0.name', 'Zed'));
});

it('refuses a sort it does not allow', function (): void {
    $this->withExceptionHandling()
        ->get(route('panel-admin::users.index', ['sort' => 'password']))
        ->assertBadRequest();
});

it('creates a user with a hashed password', function (): void {
    post(route('panel-admin::users.store'), ['name' => 'Grace', 'email' => 'Grace@He4rt.dev', 'password' => 'secret-password'])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'User created');

    $user = User::query()->where('email', 'grace@he4rt.dev')->sole();

    expect(Hash::check('secret-password', $user->password))->toBeTrue();
});

it('refuses an email that another user has', function (): void {
    User::factory()->create(['email' => 'grace@he4rt.dev']);

    $this->withExceptionHandling()
        ->post(route('panel-admin::users.store'), ['name' => 'Grace', 'email' => 'grace@he4rt.dev', 'password' => 'secret-password'])
        ->assertSessionHasErrors('email');
});

it('updates a user and keeps the password when it is empty', function (): void {
    $user = User::factory()->create(['name' => 'Grace', 'email' => 'grace@he4rt.dev']);
    $password = $user->password;

    put(route('panel-admin::users.update', $user), ['name' => 'Grace Hopper', 'email' => 'grace@he4rt.dev', 'password' => ''])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'User updated');

    expect($user->refresh())
        ->name->toBe('Grace Hopper')
        ->password->toBe($password);
});

it('deletes another user', function (): void {
    $user = User::factory()->create();

    delete(route('panel-admin::users.destroy', $user))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'User deleted');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('removes a deleted user from their teams', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user);

    delete(route('panel-admin::users.destroy', $user))->assertInertiaFlash('toast.message', 'User deleted');

    expect($team->members()->count())->toBe(0);
});

it('keeps a user who owns a team', function (): void {
    $owner = User::factory()->create();
    Team::factory()->create(['owner_id' => $owner->id]);

    delete(route('panel-admin::users.destroy', $owner))->assertInertiaFlash('toast.type', ToastType::Error->value);

    expect(User::query()->whereKey($owner->id)->exists())->toBeTrue();
});

it('does not let a super admin delete their own account', function (): void {
    $this->withExceptionHandling()
        ->delete(route('panel-admin::users.destroy', $this->admin))
        ->assertForbidden();

    assertDatabaseHas(User::class, ['id' => $this->admin->id]);
});

it('gates the list with the user policy', function (): void {
    $this->admin->roles()->first()?->revokePermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(User::class));
    $this->admin->forgetCachedPermissions();

    $this->withExceptionHandling()
        ->get(route('panel-admin::users.index'))
        ->assertForbidden();
});
