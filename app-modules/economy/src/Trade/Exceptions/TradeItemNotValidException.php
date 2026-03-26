<?php

declare(strict_types=1);

namespace He4rt\Economy\Trade\Exceptions;

use Exception;

final class TradeItemNotValidException extends Exception
{
    public static function notOwned(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s is not owned by the expected character.', $characterItemId)
        );
    }

    public static function notTradeable(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s is not tradeable.', $characterItemId)
        );
    }

    public static function currentlyEquipped(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s is currently equipped and cannot be traded.', $characterItemId)
        );
    }

    public static function inPendingTrade(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s is already in another pending trade.', $characterItemId)
        );
    }

    public static function noLongerValid(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s is no longer valid for this trade.', $characterItemId)
        );
    }
}
