<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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

    public function superAdmin(): static
    {
        return $this->withRole(UserRole::SuperAdmin);
    }

    public function staff(): static
    {
        return $this->withRole(UserRole::Staff);
    }

    public function compliance(): static
    {
        return $this->withRole(UserRole::Compliance);
    }

    public function recruiter(): static
    {
        return $this->withRole(UserRole::Recruiter);
    }

    public function squadCaptain(): static
    {
        return $this->withRole(UserRole::SquadCaptain);
    }

    private function withRole(UserRole $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            Role::findOrCreate($role->value, UserRole::GUARD);

            $user->assignRole($role);
        });
    }
}
