<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use He4rt\Identity\Teams\DTOs\TeamData;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTeam
{
    public function __construct(private AddTeamMember $addMember) {}

    /**
     * A new owner joins the team, because team access checks membership only.
     */
    public function handle(Team $team, User $owner, TeamData $data): void
    {
        DB::transaction(function () use ($team, $owner, $data): void {
            $team->update([...$data->toDatabase(), 'owner_id' => $owner->getKey()]);

            $this->addMember->handle($team, $owner);
        });
    }
}
