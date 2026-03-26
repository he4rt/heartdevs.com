<?php

declare(strict_types=1);

namespace He4rt\Economy\Database\Factories;

use He4rt\Economy\Trade\Enums\TradeDirection;
use He4rt\Economy\Trade\Models\Trade;
use He4rt\Economy\Trade\Models\TradeItem;
use He4rt\Gamification\Character\Inventory\Models\CharacterItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TradeItem>
 */
final class TradeItemFactory extends Factory
{
    protected $model = TradeItem::class;

    public function definition(): array
    {
        return [
            'trade_id' => Trade::factory(),
            'character_item_id' => CharacterItem::factory(),
            'direction' => TradeDirection::Offer,
        ];
    }

    public function asRequest(): static
    {
        return $this->state(['direction' => TradeDirection::Request]);
    }
}
