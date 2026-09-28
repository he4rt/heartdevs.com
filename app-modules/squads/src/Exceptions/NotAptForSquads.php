<?php

declare(strict_types=1);

namespace He4rt\Squads\Exceptions;

use Exception;
use He4rt\Identity\User\Models\User;

final class NotAptForSquads extends Exception
{
    public static function for(User $subject): self
    {
        return new self(
            sprintf('User "%s" must complete the Squads onboarding before applying to a squad.', $subject->id)
        );
    }
}
