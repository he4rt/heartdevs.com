<?php

declare(strict_types=1);

namespace He4rt\Identity\Tests\Feature\Permissions;

use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\Auth\AuthenticationSettings;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

it('grants the super admin exactly the permissions declared in rbac config', function (): void {
    Artisan::call('sync:permissions');

    $superAdmin = Role::query()->where('name', Roles::SuperAdmin->value)->firstOrFail();

    $expected = collect(config('rbac.identity.permissions.'.Roles::SuperAdmin->value))
        ->flatMap(fn (array $actions, string $model): array => array_map(
            fn (PermissionsEnum $action): string => $action->buildPermissionFor($model),
            $actions,
        ))
        ->sort()
        ->values();

    expect($superAdmin->permissions->pluck('name')->sort()->values()->all())
        ->toEqual($expected->all());
});

it('grants the user role only the view_user permission', function (): void {
    Artisan::call('sync:permissions');

    $userRole = Role::query()->where('name', Roles::User->value)->firstOrFail();

    expect($userRole->permissions->pluck('name')->all())
        ->toEqual([PermissionsEnum::View->buildPermissionFor(User::class)]);
});

it('logs a permissions_synced activity for a role that receives permissions', function (): void {
    Artisan::call('sync:permissions');

    $superAdmin = Role::query()->where('name', Roles::SuperAdmin->value)->firstOrFail();

    $activity = Activity::query()
        ->where('event', 'permissions_synced')
        ->where('subject_id', $superAdmin->getKey())
        ->sole();

    expect($activity->log_name)->toBe(Role::class)
        ->and($activity->description)->toBe('Role permissions updated')
        ->and(data_get($activity->attribute_changes, 'old.permissions'))->toBeEmpty()
        ->and(data_get($activity->attribute_changes, 'attributes.permissions'))->not->toBeEmpty();
});

it('does not log permission changes on an idempotent re-run', function (): void {
    Artisan::call('sync:permissions');
    $afterFirstRun = Activity::query()->where('event', 'permissions_synced')->count();

    Artisan::call('sync:permissions');

    expect(Activity::query()->where('event', 'permissions_synced')->count())
        ->toBe($afterFirstRun);
});

it('is idempotent across repeated runs', function (): void {
    Artisan::call('sync:permissions');
    Artisan::call('sync:permissions');

    $superAdmin = Role::query()->where('name', Roles::SuperAdmin->value)->firstOrFail();

    expect($superAdmin->permissions)->toHaveCount(count(PermissionsEnum::cases()) * count(Relation::morphMap()) + 2);
});

it('creates only the view and update permissions for a settings group', function (): void {
    Artisan::call('sync:permissions');

    $settingsPermissions = Permission::query()->where('resource', AuthenticationSettings::class)->orderBy('name')->get();

    expect($settingsPermissions->pluck('name')->all())->toBe(['update_authentication_settings', 'view_authentication_settings'])
        ->and($settingsPermissions->pluck('resource_group')->unique()->all())->toBe(['Settings'])
        ->and(Role::findByName(Roles::User->value)->hasPermissionTo('view_authentication_settings'))->toBeFalse();
});
