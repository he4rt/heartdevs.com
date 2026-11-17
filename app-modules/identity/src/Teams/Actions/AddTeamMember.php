<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use App\Support\Activitylog\AuditActivity;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;

/**
 * Membership lives in a pivot table, so model events never log it.
 */
final readonly class AddTeamMember
{
    /** @return bool true when the user was not a member yet */
    public function handle(Team $team, User $user): bool
    {
        $attached = $team->members()->syncWithoutDetaching([$user->getKey()])['attached'] !== [];

        if ($attached) {
            AuditActivity::event($team, 'member_added', ['user_id' => $user->getKey()], 'Member added', Team::class);
        }

        return $attached;
    }
}
