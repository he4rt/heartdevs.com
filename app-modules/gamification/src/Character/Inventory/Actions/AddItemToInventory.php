<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Inventory\Actions;

use He4rt\Gamification\Character\Inventory\DTOs\AddItemToInventoryDTO;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Exceptions\ItemException;
use He4rt\Gamification\Item\Models\Item;

final readonly class AddItemToInventory
{
    /**
     * @throws ItemException
     */
    public function handle(AddItemToInventoryDTO $dto): CharacterItem
    {
        $item = Item::query()->findOrFail($dto->itemId);

        if (!$item->active) {
            throw ItemException::notActive($dto->itemId);
        }

        $character = Character::query()->findOrFail($dto->characterId);

        if ($character->level < $item->level_required) {
            throw ItemException::levelTooLow($character->level, $item->level_required);
        }

        $exists = CharacterItem::query()
            ->where('character_id', $dto->characterId)
            ->where('item_id', $dto->itemId)
            ->exists();

        if ($exists) {
            throw ItemException::alreadyOwned($dto->characterId, $dto->itemId);
        }

        return CharacterItem::query()->create([
            'character_id' => $dto->characterId,
            'item_id' => $dto->itemId,
            'tenant_id' => $dto->tenantId,
            'acquired_via' => $dto->acquiredVia,
            'acquired_at' => now(),
        ]);
    }
}
