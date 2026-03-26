<?php

declare(strict_types=1);

namespace He4rt\Economy\Database\Factories;

use He4rt\Economy\Trade\Enums\TradeStatus;
use He4rt\Economy\Trade\Models\Trade;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
final class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'initiator_character_id' => Character::factory(),
            'receiver_character_id' => Character::factory(),
            'status' => TradeStatus::Pending,
            'resolved_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => TradeStatus::Accepted,
            'resolved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => TradeStatus::Rejected,
            'resolved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => TradeStatus::Cancelled,
            'resolved_at' => now(),
        ]);
    }
}
