<?php

declare(strict_types=1);

namespace He4rt\Profile\Database\Factories;

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Models\ProfileProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileProject>
 */
final class ProfileProjectFactory extends Factory
{
    protected $model = ProfileProject::class;

    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'name' => fake()->words(3, asText: true),
            'description' => fake()->sentence(),
            'url' => fake()->url(),
        ];
    }
}
