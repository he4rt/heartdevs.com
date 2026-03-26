<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Item\Enums\SlotType;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemSlot>
 */
final class ItemSlotFactory extends Factory
{
    protected $model = ItemSlot::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(2),
            'slot_type' => fake()->randomElement(SlotType::cases()),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
