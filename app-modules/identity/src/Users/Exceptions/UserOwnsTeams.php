<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Exceptions;

use DomainException;

final class UserOwnsTeams extends DomainException
{
    public static function make(): self
    {
        return new self('This account owns a team. Transfer the ownership or delete the team first.');
    }
}
