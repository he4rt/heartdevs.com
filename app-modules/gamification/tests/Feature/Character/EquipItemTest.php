<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Equipment\Actions\EquipItem;
use He4rt\Gamification\Character\Equipment\DTOs\EquipItemDTO;
use He4rt\Gamification\Character\Equipment\Exceptions\EquipmentException;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;

test('can equip item from inventory', function (): void {
    $tenant = Tenant::factory()->create();
    $slot = ItemSlot::factory()->recycle($tenant)->create();
    $rarity = ItemRarity::factory()->recycle($tenant)->create();
    $item = Item::factory()->recycle([$tenant, $slot, $rarity])->create(['level_required' => 0]);
    $character = Character::factory()->recycle($tenant)->create();
    $characterItem = CharacterItem::factory()->recycle([$character, $item, $tenant])->create([
        'acquired_via' => AcquisitionMethod::Drop,
    ]);

    $dto = new EquipItemDTO(
        characterId: $character->id,
        characterItemId: $characterItem->id,
    );

    $result = resolve(EquipItem::class)->handle($dto);

    $this->assertDatabaseHas('character_equipment', [
        'character_id' => $character->id,
        'slot_id' => $slot->id,
        'character_item_id' => $characterItem->id,
    ]);
});

test('equipping item swaps existing item in same slot', function (): void {
    $tenant = Tenant::factory()->create();
    $slot = ItemSlot::factory()->recycle($tenant)->create();
    $rarity = ItemRarity::factory()->recycle($tenant)->create();
    $item1 = Item::factory()->recycle([$tenant, $slot, $rarity])->create(['level_required' => 0]);
    $item2 = Item::factory()->recycle([$tenant, $slot, $rarity])->create(['level_required' => 0]);
    $character = Character::factory()->recycle($tenant)->create();
    $ci1 = CharacterItem::factory()->recycle([$character, $tenant])->create(['item_id' => $item1->id]);
    $ci2 = CharacterItem::factory()->recycle([$character, $tenant])->create(['item_id' => $item2->id]);

    $action = resolve(EquipItem::class);
    $action->handle(new EquipItemDTO($character->id, $ci1->id));
    $action->handle(new EquipItemDTO($character->id, $ci2->id));

    $this->assertDatabaseHas('character_equipment', ['character_item_id' => $ci2->id]);
    $this->assertDatabaseMissing('character_equipment', ['character_item_id' => $ci1->id]);
});

test('cannot equip item not in inventory', function (): void {
    $character = Character::factory()->create();

    $dto = new EquipItemDTO(
        characterId: $character->id,
        characterItemId: fake()->uuid(),
    );

    resolve(EquipItem::class)->handle($dto);
})->throws(EquipmentException::class);
