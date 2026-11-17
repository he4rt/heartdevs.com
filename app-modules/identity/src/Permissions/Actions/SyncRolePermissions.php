<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Actions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Permissions\Role;
use Illuminate\Support\Facades\DB;

/**
 * Spatie writes role permissions to a pivot table, so model events never log them.
 * This action syncs them and logs the before/after ids when they change.
 */
final readonly class SyncRolePermissions
{
    /**
     * @param  array<int, string>  $permissionIds
     * @return bool true when the permissions changed
     */
    public function handle(Role $role, array $permissionIds): bool
    {
        return DB::transaction(function () use ($role, $permissionIds): bool {
            $before = $this->permissionIds($role);

            $role->syncPermissions($permissionIds);

            $after = $this->permissionIds($role);

            if ($before === $after) {
                return false;
            }

            AuditActivity::changed(
                $role,
                'permissions_synced',
                attributes: ['permissions' => $after],
                old: ['permissions' => $before],
                description: 'Role permissions updated',
                log: Role::class,
            );

            return true;
        });
    }

    /** @return array<int, string> */
    private function permissionIds(Role $role): array
    {
        return $role->permissions()->pluck('id')->map(strval(...))->sort()->values()->all();
    }
}
