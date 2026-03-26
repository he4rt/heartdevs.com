<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Equipment\Exceptions;

use Exception;

final class EquipmentException extends Exception
{
    public static function itemNotInInventory(string $characterItemId): self
    {
        return new self(
            sprintf('Character item %s not found in inventory.', $characterItemId)
        );
    }

    public static function notEquipped(string $characterId, int $slotId): self
    {
        return new self(
            sprintf('Character %s has nothing equipped in slot %d.', $characterId, $slotId)
        );
    }
}
