<?php

declare(strict_types=1);

namespace He4rt\Identity\User\Policies;

use He4rt\Identity\User\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canViewUsers();
    }

    public function view(User $user): bool
    {
        return $user->role->canViewUsers();
    }

    public function update(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function delete(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function restore(User $user): bool
    {
        return $user->role->isCompliance();
    }

    public function forceDelete(User $user): bool
    {
        return $user->role->isCompliance();
    }
}
