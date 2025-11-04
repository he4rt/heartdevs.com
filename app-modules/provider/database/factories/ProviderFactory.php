<?php

declare(strict_types=1);

namespace He4rt\Provider\Database\Factories;

use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'tenant_id' => Tenant::factory(),
            'model_type' => User::class,
            'model_id' => User::factory(),
            'provider' => fake()->randomElement(['discord']),
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
