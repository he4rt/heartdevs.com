<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\Exceptions;

use DomainException;

final class CannotImpersonate extends DomainException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
