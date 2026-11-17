<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Actions;

use He4rt\Identity\Teams\Team;

final readonly class RestoreTeam
{
    public function handle(Team $team): void
    {
        $team->restore();
    }
}
