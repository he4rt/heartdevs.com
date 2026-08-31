<?php

declare(strict_types=1);

namespace He4rt\Squads\Exceptions;

use Exception;
use He4rt\Squads\Enums\SquadRole;

final class InvalidSquadRoleTransition extends Exception
{
    public static function between(SquadRole $from, SquadRole $to): self
    {
        return new self(
            sprintf('Cannot transition squad member role from "%s" to "%s".', $from->value, $to->value)
        );
    }
}
