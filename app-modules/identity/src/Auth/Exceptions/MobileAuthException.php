<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Exceptions;

use RuntimeException;

final class MobileAuthException extends RuntimeException
{
    public static function invalidExchangeCode(): self
    {
        return new self('Invalid or expired exchange code.');
    }
}
