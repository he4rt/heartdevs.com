<?php

declare(strict_types=1);

namespace He4rt\Economy\Exceptions;

use Exception;

final class InsufficientBalanceException extends Exception
{
    public static function forAmount(int $requested, int $available): self
    {
        return new self(
            sprintf('Insufficient balance: requested %d but only %d available.', $requested, $available)
        );
    }
}
