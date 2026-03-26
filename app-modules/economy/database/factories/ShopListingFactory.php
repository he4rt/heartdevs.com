<?php

declare(strict_types=1);

namespace He4rt\Economy\Database\Factories;

use He4rt\Economy\Shop\Models\ShopListing;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopListing>
 */
final class ShopListingFactory extends Factory
{
    protected $model = ShopListing::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'item_id' => Item::factory(),
            'price' => fake()->numberBetween(10, 500),
            'stock' => null,
            'available_from' => null,
            'available_until' => null,
            'active' => true,
        ];
    }

    public function limited(int $stock = 10): static
    {
        return $this->state(['stock' => $stock]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
