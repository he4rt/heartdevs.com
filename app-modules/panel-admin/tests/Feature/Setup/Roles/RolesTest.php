<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function (): void {
    actingAs(User::factory()->superAdmin()->create());

    $this->permission = fn (PermissionsEnum $action, string $model = User::class): string => (string) Permission::findByName($action->buildPermissionFor($model))->getKey();
    $this->permissionsSynced = fn (Role $role) => Activity::query()->where('event', 'permissions_synced')->where('subject_id', $role->getKey());
});

it('lists roles with counts and marks the system roles', function (): void {
    Role::factory()->create(['name' => 'Support']);

    get(route('panel-admin::roles.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('roles/index')
            ->has('roles.data', 3)
            ->where('roles.data.0.name', 'Support')
            ->where('roles.data.0.isSystem', expected: false)
            ->where('roles.data.1.name', Roles::SuperAdmin->value)
            ->where('roles.data.1.isSystem', expected: true)
            ->where('roles.data.1.usersCount', 1)
            ->where('can.create', expected: true));
});

it('builds the permission matrix grouped by resource group and ordered by action', function (): void {
    get(route('panel-admin::roles.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('roles/form')
            ->where('role', expected: null)
            ->where('matrix.total', Permission::query()->count())
            ->where('matrix.actions.0.key', 'view_any')
            ->where('matrix.actions.1.key', 'view')
            ->where('matrix.groups.0.key', 'Identity')
            ->has('matrix.groups.0.resources.0.permissions.view_any'));
});

it('creates a role with permissions and logs them', function (): void {
    $permissions = [($this->permission)(PermissionsEnum::ViewAny), ($this->permission)(PermissionsEnum::View)];

    post(route('panel-admin::roles.store'), ['name' => ' Support ', 'permissions' => $permissions])
        ->assertRedirect(route('panel-admin::roles.index'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Role created');

    $role = Role::findByName('Support');

    expect($role->permissions->pluck('id')->map(strval(...))->all())->toEqualCanonicalizing($permissions)
        ->and(data_get(($this->permissionsSynced)($role)->sole()->attribute_changes, 'old.permissions'))->toBe([]);
});

it('updates the name and logs the permission diff', function (): void {
    $role = Role::factory()->create(['name' => 'Support']);
    $view = ($this->permission)(PermissionsEnum::View);
    $update = ($this->permission)(PermissionsEnum::Update);
    $role->syncPermissions([$view]);

    put(route('panel-admin::roles.update', $role), ['name' => 'Customer Support', 'permissions' => [$view, $update]])
        ->assertRedirect(route('panel-admin::roles.index'))
        ->assertSessionHasNoErrors();

    $activity = ($this->permissionsSynced)($role)->sole();

    expect($role->refresh()->name)->toBe('Customer Support')
        ->and(data_get($activity->attribute_changes, 'old.permissions'))->toBe([$view])
        ->and(data_get($activity->attribute_changes, 'attributes.permissions'))->toEqualCanonicalizing([$view, $update]);
});

it('writes no permission activity when the permissions stay the same', function (): void {
    $role = Role::factory()->create(['name' => 'Support']);
    $view = ($this->permission)(PermissionsEnum::View);
    $role->syncPermissions([$view]);

    put(route('panel-admin::roles.update', $role), ['name' => 'Support', 'permissions' => [$view]])->assertSessionHasNoErrors();

    expect(($this->permissionsSynced)($role)->exists())->toBeFalse();
});

it('refuses invalid input', function (array $payload, string $field): void {
    Role::factory()->create(['name' => 'Support']);

    $this->withExceptionHandling()
        ->post(route('panel-admin::roles.store'), $payload)
        ->assertSessionHasErrors($field);
})->with([
    'a taken name' => [['name' => 'Support'], 'name'],
    'a system role name' => [['name' => Roles::User->value], 'name'],
    'an unknown permission' => [['name' => 'Ops', 'permissions' => ['00000000-0000-0000-0000-000000000000']], 'permissions.0'],
]);

it('shows a system role read-only', function (): void {
    get(route('panel-admin::roles.edit', Role::findByName(Roles::User->value)))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('role.isSystem', expected: true)
            ->where('can.update', expected: false));
});

it('refuses to change or delete a system role', function (): void {
    $role = Role::findByName(Roles::SuperAdmin->value);

    $this->withExceptionHandling()
        ->put(route('panel-admin::roles.update', $role), ['name' => 'root', 'permissions' => []])
        ->assertForbidden();

    $this->withExceptionHandling()
        ->delete(route('panel-admin::roles.destroy', $role))
        ->assertForbidden();

    expect($role->refresh()->name)->toBe(Roles::SuperAdmin->value)
        ->and($role->permissions)->not->toBeEmpty();
});

it('deletes a custom role', function (): void {
    $role = Role::factory()->create(['name' => 'Support']);

    $this->delete(route('panel-admin::roles.destroy', $role))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Role deleted');

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});
