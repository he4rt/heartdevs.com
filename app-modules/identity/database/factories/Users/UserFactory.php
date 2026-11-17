<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories\Users;

use App\Enums\Language;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    private static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'locale' => fake()->randomElement(Language::cases())->value,
            'theme_color' => fake()->hexColor(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * A person who signed up with a provider and has not set a password.
     */
    public function withoutPassword(): self
    {
        return $this->state(['password' => null]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a self-contained admin
     */
    public function admin(): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'admin',
            'email' => 'admin@admin.com',
        ])->afterCreating(static function (User $user): void {
            $user->assignRole(Roles::SuperAdmin);
        });
    }

    /**
     * Two factor on and confirmed, with a fixed secret so a test can make a valid code.
     */
    public function withTwoFactor(string $secret = 'JBSWY3DPEHPK3PXP'): self
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1', 'recovery-code-2'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function superAdmin(): self
    {
        return $this->afterCreating(static function (User $user): void {
            $user->assignRole(Roles::SuperAdmin);
        });
    }

    /**
     * Create a self-contained user
     */
    public function user(): self
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'user',
            'email' => 'user@user.com',
        ])->afterCreating(static function (User $user): void {
            $user->assignRole(Roles::User);
        });
    }
}
