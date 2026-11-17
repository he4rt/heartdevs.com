<?php

declare(strict_types=1);

namespace He4rt\Identity\Teams\Exceptions;

use DomainException;
use He4rt\Identity\Teams\Team;

final class TeamOwnerCannotLeave extends DomainException
{
    public static function of(Team $team): self
    {
        return new self(sprintf('The owner of %s cannot leave it. Transfer the ownership first.', $team->name));
    }
}
