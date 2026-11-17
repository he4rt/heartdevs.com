<?php

declare(strict_types=1);

use He4rt\Identity\Permissions\Actions\SyncRolePermissions;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\Role;
use Spatie\Activitylog\Models\Activity;

function permissionsSyncedFor(Role $role): ?Activity
{
    return Activity::query()
        ->where('event', 'permissions_synced')
        ->where('subject_id', $role->getKey())
        ->latest('id')
        ->first();
}

it('syncs the permissions and logs the before and after ids', function (): void {
    $role = Role::factory()->create();
    [$first, $second] = Permission::query()->orderBy('id')->limit(2)->pluck('id')->map(strval(...))->all();
    $role->syncPermissions([$first]);

    $changed = resolve(SyncRolePermissions::class)->handle($role, [$second, $first]);

    expect($changed)->toBeTrue()
        ->and($role->permissions()->pluck('id')->map(strval(...))->sort()->values()->all())->toBe([$first, $second])
        ->and(data_get(permissionsSyncedFor($role)?->attribute_changes, 'old.permissions'))->toBe([$first])
        ->and(data_get(permissionsSyncedFor($role)?->attribute_changes, 'attributes.permissions'))->toBe([$first, $second]);
});

it('writes nothing when the permissions do not change', function (): void {
    $role = Role::factory()->create();
    $ids = Permission::query()->limit(2)->pluck('id')->map(strval(...))->all();
    $role->syncPermissions($ids);

    $changed = resolve(SyncRolePermissions::class)->handle($role, array_reverse($ids));

    expect($changed)->toBeFalse()
        ->and(permissionsSyncedFor($role))->toBeNull();
});
