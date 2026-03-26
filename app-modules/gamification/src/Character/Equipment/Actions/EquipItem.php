<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Equipment\Actions;

use He4rt\Gamification\Character\Equipment\DTOs\EquipItemDTO;
use He4rt\Gamification\Character\Equipment\Exceptions\EquipmentException;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Exceptions\ItemException;

final readonly class EquipItem
{
    /**
     * @throws EquipmentException
     * @throws ItemException
     */
    public function handle(EquipItemDTO $dto): CharacterEquipment
    {
        $characterItem = CharacterItem::query()
            ->where('id', $dto->characterItemId)
            ->where('character_id', $dto->characterId)
            ->first();

        if (!$characterItem) {
            throw EquipmentException::itemNotInInventory($dto->characterItemId);
        }

        $item = $characterItem->item;
        $character = Character::query()->findOrFail($dto->characterId);

        if ($character->level < $item->level_required) {
            throw ItemException::levelTooLow($character->level, $item->level_required);
        }

        // Auto-unequip existing item in the same slot (swap)
        CharacterEquipment::query()
            ->where('character_id', $dto->characterId)
            ->where('slot_id', $item->slot_id)
            ->delete();

        return CharacterEquipment::query()->create([
            'character_id' => $dto->characterId,
            'slot_id' => $item->slot_id,
            'character_item_id' => $characterItem->id,
            'tenant_id' => $characterItem->tenant_id,
            'equipped_at' => now(),
        ]);
    }
}
