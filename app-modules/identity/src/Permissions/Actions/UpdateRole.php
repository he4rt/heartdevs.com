<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions\Actions;

use He4rt\Identity\Permissions\DTOs\RoleData;
use He4rt\Identity\Permissions\Role;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRole
{
    public function __construct(private SyncRolePermissions $syncPermissions) {}

    public function handle(Role $role, RoleData $data): void
    {
        DB::transaction(function () use ($role, $data): void {
            $role->update($data->toDatabase());

            $this->syncPermissions->handle($role, $data->permissionIds);
        });
    }
}
