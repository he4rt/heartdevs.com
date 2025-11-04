<?php

declare(strict_types=1);

namespace He4rt\Character\Database\Factories;

use He4rt\Character\Models\Character;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
final class CharacterFactory extends Factory
{
    protected $model = Character::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'reputation' => fake()->numberBetween(1, 10),
            'experience' => fake()->numberBetween(1, 5000),
            'daily_bonus_claimed_at' => now(),
        ];
    }
}
