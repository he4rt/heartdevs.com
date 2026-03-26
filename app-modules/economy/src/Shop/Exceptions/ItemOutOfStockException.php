<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\Exceptions;

use Exception;

final class ItemOutOfStockException extends Exception
{
    public static function forListing(string $listingId): self
    {
        return new self(
            sprintf('Shop listing %s is out of stock.', $listingId)
        );
    }
}
