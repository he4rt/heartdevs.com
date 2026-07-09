<?php

declare(strict_types=1);

namespace He4rt\Squads\Exceptions;

use Exception;

final class MembershipEventIsImmutable extends Exception
{
    public static function make(): self
    {
        return new self('Squad membership events are append-only and cannot be updated or deleted.');
    }
}
