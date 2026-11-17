<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create();
    $this->role = Role::factory()->create(['name' => 'Support']);

    actingAs($this->admin);

    $this->snapshot = fn (): array => [
        DB::table('identity_roles')->orderBy('id')->get()->all(),
        DB::table('identity_role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->all(),
    ];
});

it('refuses each endpoint without its permission and changes nothing', function (PermissionsEnum $permission, string $method, string $routeName, bool $onRole, array $payload): void {
    $this->admin->roles()->first()?->revokePermissionTo($permission->buildPermissionFor(Role::class));
    $this->admin->forgetCachedPermissions();
    $before = ($this->snapshot)();

    $this->withExceptionHandling()
        ->call($method, route('panel-admin::'.$routeName, $onRole ? $this->role : []), $payload)
        ->assertForbidden();

    expect(($this->snapshot)())->toEqual($before);
})->with([
    'list' => [PermissionsEnum::ViewAny, 'GET', 'roles.index', false, []],
    'new' => [PermissionsEnum::Create, 'GET', 'roles.create', false, []],
    'create' => [PermissionsEnum::Create, 'POST', 'roles.store', false, ['name' => 'Billing', 'permissions' => []]],
    'edit' => [PermissionsEnum::View, 'GET', 'roles.edit', true, []],
    'update' => [PermissionsEnum::Update, 'PUT', 'roles.update', true, ['name' => 'Changed', 'permissions' => []]],
    'delete' => [PermissionsEnum::Delete, 'DELETE', 'roles.destroy', true, []],
]);
