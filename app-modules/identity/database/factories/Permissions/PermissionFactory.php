<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories\Permissions;

use He4rt\Identity\Permissions\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<Permission> */
final class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'guard_name' => fake()->name(),
            'name' => fake()->name(),
            'resource' => fake()->word(),
            'resource_group' => fake()->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
