<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
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
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRole::SuperAdmin->value, UserRole::GUARD);

            $user->assignRole(UserRole::SuperAdmin);
        });
    }

    public function streamer(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRole::Streamer->value, UserRole::GUARD);

            $user->assignRole(UserRole::Streamer);
        });
    }

    public function delasModerator(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRole::DelasModerator->value, UserRole::GUARD);

            $user->assignRole(UserRole::DelasModerator);
        });
    }

    /**
     * Conta do Discord conectada pela própria pessoa.
     */
    public function withDiscord(): static
    {
        return $this->afterCreating(function (User $user): void {
            ExternalIdentity::factory()->create([
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
                'provider' => IdentityProvider::Discord,
                'connected_by' => $user->getKey(),
            ]);
        });
    }

    public function delasLead(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRole::DelasLead->value, UserRole::GUARD);

            $user->assignRole(UserRole::DelasLead);
        });
    }
}
