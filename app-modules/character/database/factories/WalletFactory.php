<?php

declare(strict_types=1);

namespace He4rt\Character\Database\Factories;

use He4rt\Character\Models\Character;
use He4rt\Character\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
final class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'balance' => fake()->randomNumber(1, 99999),
        ];
    }
}
