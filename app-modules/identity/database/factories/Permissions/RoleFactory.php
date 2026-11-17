<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories\Permissions;

use He4rt\Identity\Permissions\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<Role> */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'guard_name' => config()->string('auth.defaults.guard'),
            'name' => fake()->unique()->jobTitle(),
        ];
    }
}
