<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Enums\AcquisitionMethod;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacterItem>
 */
final class CharacterItemFactory extends Factory
{
    protected $model = CharacterItem::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'character_id' => Character::factory(),
            'item_id' => Item::factory(),
            'tenant_id' => Tenant::factory(),
            'acquired_via' => fake()->randomElement(AcquisitionMethod::cases()),
            'acquired_at' => now(),
        ];
    }
}
