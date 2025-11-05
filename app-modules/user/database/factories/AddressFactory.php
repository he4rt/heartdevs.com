<?php

declare(strict_types=1);

namespace He4rt\User\Database\Factories;

use He4rt\User\Models\Address;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
final class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'country' => fake()->countryCode(),
            'state' => fake()->randomElement(['SP', 'RJ', 'BH']),
            'city' => fake()->city(),
            'zip_code' => fake()->randomNumber(8),
        ];
    }
}
