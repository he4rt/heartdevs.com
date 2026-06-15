<?php

declare(strict_types=1);

namespace He4rt\Economy\Database\Factories;

use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Models\Transaction;
use He4rt\Economy\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
final class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'type' => TransactionType::Reward,
            'amount' => fake()->numberBetween(1, 100),
            'balance_after' => fake()->numberBetween(0, 1_000),
            'description' => fake()->sentence(),
        ];
    }
}
