<?php

declare(strict_types=1);

namespace He4rt\Identity\Permissions;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->can($user, PermissionsEnum::ViewAny);
    }

    public function view(User $user): bool
    {
        return $this->can($user, PermissionsEnum::View);
    }

    public function create(User $user): bool
    {
        return $this->can($user, PermissionsEnum::Create);
    }

    public function update(User $user, Role $role): bool
    {
        return !$role->isSystem() && $this->can($user, PermissionsEnum::Update);
    }

    public function delete(User $user, Role $role): bool
    {
        return !$role->isSystem() && $this->can($user, PermissionsEnum::Delete);
    }

    public function restore(User $user): bool
    {
        return $this->can($user, PermissionsEnum::Restore);
    }

    public function forceDelete(User $user): bool
    {
        return $this->can($user, PermissionsEnum::ForceDelete);
    }

    private function can(User $user, PermissionsEnum $action): bool
    {
        return $user->hasPermissionTo($action->buildPermissionFor(Role::class));
    }
}
