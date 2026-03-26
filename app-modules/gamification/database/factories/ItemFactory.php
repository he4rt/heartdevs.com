<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Item\Models\Item;
use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Gamification\Item\Models\ItemSlot;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
final class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'slot_id' => ItemSlot::factory(),
            'rarity_id' => ItemRarity::factory(),
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'is_tradeable' => true,
            'is_purchasable' => false,
            'price' => null,
            'drop_rate' => null,
            'level_required' => 0,
            'active' => true,
            'metadata' => null,
        ];
    }
}
