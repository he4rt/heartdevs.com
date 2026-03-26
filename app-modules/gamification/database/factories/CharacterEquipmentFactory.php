<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterEquipment>
 */
final class CharacterEquipmentFactory extends Factory
{
    protected $model = CharacterEquipment::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'character_id' => Character::factory(),
            'slot_id' => ItemSlot::factory(),
            'character_item_id' => CharacterItem::factory(),
            'tenant_id' => Tenant::factory(),
            'equipped_at' => now(),
        ];
    }
}
