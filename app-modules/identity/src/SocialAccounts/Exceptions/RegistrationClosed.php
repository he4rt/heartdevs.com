<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts\Exceptions;

use DomainException;

final class RegistrationClosed extends DomainException
{
    public static function make(): self
    {
        return new self('No account uses this email, and sign up is closed.');
    }
}
