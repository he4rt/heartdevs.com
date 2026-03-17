<?php

declare(strict_types=1);

namespace He4rt\Economy\Database\Factories;

use He4rt\Economy\Enums\Currency;
use He4rt\Economy\Models\Wallet;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Wallet> */
final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'owner_type' => Character::class,
            'owner_id' => Character::factory(),
            'currency' => Currency::Coin,
            'balance' => 0,
        ];
    }
}
