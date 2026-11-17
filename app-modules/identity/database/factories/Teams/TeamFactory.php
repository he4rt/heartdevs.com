<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories\Teams;

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Team> */
final class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->text(),
            'slug' => fake()->slug(),
            'status' => TeamStatus::Active->value,
            'contact_email' => fake()->unique()->safeEmail(),

            'owner_id' => User::factory(),
        ];
    }
}
