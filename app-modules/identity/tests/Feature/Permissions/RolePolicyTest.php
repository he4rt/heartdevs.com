<?php

declare(strict_types=1);

namespace He4rt\Identity\Tests\Feature\Permissions;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Artisan::call('sync:permissions');
});

it('allows super admin to perform all role actions', function (): void {
    $superUser = User::factory()->create();
    $superUser->assignRole(Roles::SuperAdmin->value);

    $role = Role::factory()->create(['name' => 'Support']);

    expect(Gate::forUser($superUser)->allows('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('view', $role))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('create', Role::class))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('update', $role))->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('delete', $role))->toBeTrue();
});

it('denies a user without permissions every role action', function (): void {
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => Roles::User->value, 'guard_name' => 'web']);

    expect(Gate::forUser($user)->denies('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $role))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Role::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', $role))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $role))->toBeTrue();
});

it('allows a user granted a specific role permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Role::class));

    expect(Gate::forUser($user)->allows('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Role::class))->toBeTrue();
});

it('keeps system roles read-only, even for a super admin', function (Roles $systemRole): void {
    $superUser = User::factory()->create();
    $superUser->assignRole(Roles::SuperAdmin->value);

    $role = Role::findByName($systemRole->value);

    expect($role->isSystem())->toBeTrue()
        ->and(Gate::forUser($superUser)->allows('view', $role))->toBeTrue()
        ->and(Gate::forUser($superUser)->denies('update', $role))->toBeTrue()
        ->and(Gate::forUser($superUser)->denies('delete', $role))->toBeTrue();
})->with(Roles::cases());
