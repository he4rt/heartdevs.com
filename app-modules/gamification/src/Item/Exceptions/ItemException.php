<?php

declare(strict_types=1);

namespace He4rt\Gamification\Item\Exceptions;

use Exception;

final class ItemException extends Exception
{
    public static function alreadyOwned(string $characterId, string $itemId): self
    {
        return new self(
            sprintf('Character %s already owns item %s.', $characterId, $itemId)
        );
    }

    public static function notActive(string $itemId): self
    {
        return new self(
            sprintf('Item %s is not active.', $itemId)
        );
    }

    public static function levelTooLow(int $characterLevel, int $requiredLevel): self
    {
        return new self(
            sprintf('Character level %d is below the required level %d.', $characterLevel, $requiredLevel)
        );
    }
}
