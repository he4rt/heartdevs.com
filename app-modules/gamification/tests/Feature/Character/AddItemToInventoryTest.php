<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Inventory\Actions\AddItemToInventory;
use He4rt\Gamification\Character\Inventory\DTOs\AddItemToInventoryDTO;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use He4rt\Gamification\Item\Exceptions\ItemException;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;

test('can add item to character inventory', function (): void {
    $tenant = Tenant::factory()->create();
    $slot = ItemSlot::factory()->recycle($tenant)->create();
    $rarity = ItemRarity::factory()->recycle($tenant)->create();
    $item = Item::factory()->recycle([$tenant, $slot, $rarity])->create([
        'level_required' => 0,
        'active' => true,
    ]);
    $character = Character::factory()->recycle($tenant)->create(['experience' => 500]);

    $dto = new AddItemToInventoryDTO(
        characterId: $character->id,
        itemId: $item->id,
        tenantId: $tenant->id,
        acquiredVia: AcquisitionMethod::Drop,
    );

    $result = resolve(AddItemToInventory::class)->handle($dto);

    expect($result->character_id)->toBe($character->id)
        ->and($result->item_id)->toBe($item->id)
        ->and($result->acquired_via)->toBe(AcquisitionMethod::Drop);

    $this->assertDatabaseHas('character_items', [
        'character_id' => $character->id,
        'item_id' => $item->id,
        'acquired_via' => 'drop',
    ]);
});

test('cannot add inactive item to inventory', function (): void {
    $tenant = Tenant::factory()->create();
    $item = Item::factory()->recycle($tenant)->create(['active' => false]);
    $character = Character::factory()->recycle($tenant)->create();

    $dto = new AddItemToInventoryDTO(
        characterId: $character->id,
        itemId: $item->id,
        tenantId: $tenant->id,
        acquiredVia: AcquisitionMethod::Purchase,
    );

    resolve(AddItemToInventory::class)->handle($dto);
})->throws(ItemException::class);

test('cannot add item when character level is too low', function (): void {
    $tenant = Tenant::factory()->create();
    $item = Item::factory()->recycle($tenant)->create([
        'level_required' => 50,
        'active' => true,
    ]);
    $character = Character::factory()->recycle($tenant)->create(['experience' => 0]);

    $dto = new AddItemToInventoryDTO(
        characterId: $character->id,
        itemId: $item->id,
        tenantId: $tenant->id,
        acquiredVia: AcquisitionMethod::Drop,
    );

    resolve(AddItemToInventory::class)->handle($dto);
})->throws(ItemException::class);

test('cannot add duplicate item to inventory', function (): void {
    $tenant = Tenant::factory()->create();
    $item = Item::factory()->recycle($tenant)->create([
        'active' => true,
        'level_required' => 0,
    ]);
    $character = Character::factory()->recycle($tenant)->create();

    $dto = new AddItemToInventoryDTO(
        characterId: $character->id,
        itemId: $item->id,
        tenantId: $tenant->id,
        acquiredVia: AcquisitionMethod::Reward,
    );

    $action = resolve(AddItemToInventory::class);
    $action->handle($dto);
    $action->handle($dto);
})->throws(ItemException::class);
