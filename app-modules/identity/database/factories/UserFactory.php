<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make('password'),
            'is_donator' => false,
        ];
    }
}
