<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Actions;

use He4rt\Identity\Permissions\DTOs\RoleData;
use He4rt\Identity\Permissions\Role;
use Illuminate\Support\Facades\DB;

final readonly class CreateRole
{
    public function __construct(private SyncRolePermissions $syncPermissions) {}

    public function handle(RoleData $data, string $guardName): Role
    {
        return DB::transaction(function () use ($data, $guardName): Role {
            $role = Role::query()->create([...$data->toDatabase(), 'guard_name' => $guardName]);

            $this->syncPermissions->handle($role, $data->permissionIds);

            return $role;
        });
    }
}
