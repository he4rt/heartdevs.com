<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalIdentity>
 */
final class ExternalIdentityFactory extends Factory
{
    protected $model = ExternalIdentity::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'model_type' => User::class,
            'model_id' => User::factory(),
            'provider' => fake()->randomElement(IdentityProvider::cases()),
            'provider_id' => fake()->numerify('######'),
            'email' => fake()->unique()->email(),
        ];
    }

    public function morphFor(?string $model = User::class): static
    {
        return $this->state(fn () => [
            'model_type' => $model,
            'model_id' => $model::factory(),
        ]);
    }
}
