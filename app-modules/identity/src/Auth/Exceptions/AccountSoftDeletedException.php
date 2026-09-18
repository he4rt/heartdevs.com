<?php

declare(strict_types=1);

namespace He4rt\Identity\Auth\Exceptions;

final class AccountSoftDeletedException extends OAuthFlowException
{
    public static function make(): self
    {
        return new self('This account was deleted and cannot be reactivated by logging in again.');
    }
}
