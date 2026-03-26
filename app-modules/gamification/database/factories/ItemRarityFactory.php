<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemRarity>
 */
final class ItemRarityFactory extends Factory
{
    protected $model = ItemRarity::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(2),
            'color' => fake()->hexColor(),
            'drop_weight' => fake()->numberBetween(1, 1000),
        ];
    }
}
