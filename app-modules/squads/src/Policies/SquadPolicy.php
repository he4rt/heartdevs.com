<?php

declare(strict_types=1);

namespace He4rt\Squads\Policies;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use Illuminate\Auth\Access\AuthorizationException;

final class SquadPolicy
{
    public function canManage(User $actor, Squad $squad): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return SquadMember::query()
            ->where('squad_id', $squad->id)
            ->where('user_id', $actor->id)
            ->whereIn('role', [SquadRole::Captain, SquadRole::SubCaptain])
            ->exists();
    }

    public function authorize(User $actor, Squad $squad): void
    {
        throw_unless($this->canManage($actor, $squad), AuthorizationException::class);
    }

    public function canManageSubCaptains(User $actor, Squad $squad): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return SquadMember::query()
            ->where('squad_id', $squad->id)
            ->where('user_id', $actor->id)
            ->where('role', SquadRole::Captain)
            ->exists();
    }

    public function authorizeSubCaptainManagement(User $actor, Squad $squad): void
    {
        throw_unless($this->canManageSubCaptains($actor, $squad), AuthorizationException::class);
    }
}
