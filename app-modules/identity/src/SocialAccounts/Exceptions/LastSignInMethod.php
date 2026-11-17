<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;

final class LastSignInMethod extends DomainException
{
    public static function make(): self
    {
        return new self('Set a password or add a passkey before you disconnect your last account.');
    }
}
