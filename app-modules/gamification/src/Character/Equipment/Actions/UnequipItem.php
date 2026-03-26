<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Equipment\Actions;

use He4rt\Gamification\Character\Equipment\Exceptions\EquipmentException;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;

final readonly class UnequipItem
{
    /**
     * @throws EquipmentException
     */
    public function handle(string $characterId, int $slotId): void
    {
        $equipment = CharacterEquipment::query()
            ->where('character_id', $characterId)
            ->where('slot_id', $slotId)
            ->first();

        if (!$equipment) {
            throw EquipmentException::notEquipped($characterId, $slotId);
        }

        $equipment->delete();
    }
}
