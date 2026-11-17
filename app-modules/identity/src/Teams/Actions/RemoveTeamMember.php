<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Teams\Exceptions\TeamOwnerCannotLeave;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

final readonly class RemoveTeamMember
{
    /**
     * @return bool true when the user was a member
     *
     * @throws TeamOwnerCannotLeave
     */
    public function handle(Team $team, User $user): bool
    {
        throw_if($team->owner_id === $user->getKey(), TeamOwnerCannotLeave::of($team));

        $detached = $team->members()->detach($user->getKey()) > 0;

        if ($detached) {
            AuditActivity::event($team, 'member_removed', ['user_id' => $user->getKey()], 'Member removed', Team::class);
        }

        return $detached;
    }
}
