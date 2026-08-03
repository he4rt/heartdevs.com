<?php

declare(strict_types=1);

namespace He4rt\Squads\Exceptions;

use Exception;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Models\Squad;

final class NotAnActiveSquadMember extends Exception
{
    public static function for(Squad $squad, User $subject): self
    {
        return new self(
            sprintf('User "%s" holds no active membership in squad "%s".', $subject->id, $squad->id)
        );
    }
}
