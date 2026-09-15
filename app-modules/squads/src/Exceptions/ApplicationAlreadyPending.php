<?php

declare(strict_types=1);

namespace He4rt\Squads\Exceptions;

use Exception;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Models\Squad;

final class ApplicationAlreadyPending extends Exception
{
    public static function for(Squad $squad, User $applicant): self
    {
        return new self(
            sprintf('User "%s" already has a pending application to squad "%s".', $applicant->id, $squad->id)
        );
    }
}
