<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Equipment\Actions\UnequipItem;
use He4rt\Gamification\Character\Equipment\Exceptions\EquipmentException;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;

test('can unequip item from slot', function (): void {
    $tenant = Tenant::factory()->create();
    $slot = ItemSlot::factory()->recycle($tenant)->create();
    $rarity = ItemRarity::factory()->recycle($tenant)->create();
    $item = Item::factory()->recycle([$tenant, $slot, $rarity])->create();
    $character = Character::factory()->recycle($tenant)->create();
    $ci = CharacterItem::factory()->recycle([$character, $item, $tenant])->create([
        'acquired_via' => AcquisitionMethod::Drop,
    ]);
    CharacterEquipment::factory()->recycle([$character, $slot, $tenant])->create([
        'character_item_id' => $ci->id,
    ]);

    resolve(UnequipItem::class)->handle($character->id, $slot->id);

    $this->assertDatabaseMissing('character_equipment', [
        'character_id' => $character->id,
        'slot_id' => $slot->id,
    ]);

    // Item remains in inventory
    $this->assertDatabaseHas('character_items', [
        'character_id' => $character->id,
        'item_id' => $item->id,
    ]);
});

test('cannot unequip empty slot', function (): void {
    $character = Character::factory()->create();
    $slot = ItemSlot::factory()->create();

    resolve(UnequipItem::class)->handle($character->id, $slot->id);
})->throws(EquipmentException::class);
