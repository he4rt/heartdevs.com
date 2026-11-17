<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams;

use He4rt\Identity\Permissions\PermissionsEnum;
use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TeamPolicy
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

    public function update(User $user, Team $team): bool
    {
        return !$team->trashed() && $this->can($user, PermissionsEnum::Update);
    }

    public function delete(User $user, Team $team): bool
    {
        return !$team->trashed() && $this->can($user, PermissionsEnum::Delete);
    }

    public function restore(User $user, Team $team): bool
    {
        return $team->trashed() && $this->can($user, PermissionsEnum::Restore);
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $team->trashed() && $this->can($user, PermissionsEnum::ForceDelete);
    }

    private function can(User $user, PermissionsEnum $action): bool
    {
        return $user->hasPermissionTo($action->buildPermissionFor(Team::class));
    }
}
