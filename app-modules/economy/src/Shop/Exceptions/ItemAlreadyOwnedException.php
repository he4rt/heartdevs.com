<?php

declare(strict_types=1);

namespace He4rt\Economy\Shop\Exceptions;

use Exception;

final class ItemAlreadyOwnedException extends Exception
{
    public static function forCharacter(string $characterId, string $itemId): self
    {
        return new self(
            sprintf('Character %s already owns item %s.', $characterId, $itemId)
        );
    }
}
