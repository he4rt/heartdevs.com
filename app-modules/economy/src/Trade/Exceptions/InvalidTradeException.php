<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Exceptions;

use Exception;

final class InvalidTradeException extends Exception
{
    public static function selfTrade(): self
    {
        return new self('Cannot trade with yourself.');
    }

    public static function notPending(string $tradeId): self
    {
        return new self(
            sprintf('Trade %s is not in pending status.', $tradeId)
        );
    }

    public static function notAuthorized(): self
    {
        return new self('You are not authorized to perform this action on this trade.');
    }
}
