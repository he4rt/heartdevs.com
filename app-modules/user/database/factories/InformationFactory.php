<?php

declare(strict_types=1);

namespace He4rt\User\Database\Factories;

use He4rt\User\Models\Information;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Information>
 */
final class InformationFactory extends Factory
{
    protected $model = Information::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'nickname' => fake()->userName(),
            'linkedin_url' => fake()->url(),
            'github_url' => fake()->url(),
            'birthdate' => fake()->date(),
            'about' => fake()->text(),
        ];
    }
}
