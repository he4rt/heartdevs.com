<?php

declare(strict_types=1);

namespace He4rt\Gamification\Providers;

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Gamification\Character\Equipment\Models\CharacterEquipment;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Item\Models\Item;
use He4rt\Gamification\Item\Models\ItemRarity;
use He4rt\Gamification\Item\Models\ItemSlot;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class GamificationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Relation::morphMap([
            'character' => Character::class,
            'badge' => Badge::class,
            'item' => Item::class,
            'item_slot' => ItemSlot::class,
            'item_rarity' => ItemRarity::class,
            'character_item' => CharacterItem::class,
            'character_equipment' => CharacterEquipment::class,
        ]);
    }
}
