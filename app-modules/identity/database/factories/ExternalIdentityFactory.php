<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\ExternalIdentity\Data\ClientAccessManager;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Enums\IdentityType;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

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
            'type' => IdentityType::External,
            'provider' => fake()->randomElement(IdentityProvider::cases()),
            'credentials_type' => CredentialsType::OAuth2,
            'credentials' => ClientAccessManager::make(
                accessToken: Crypt::encrypt(fake()->sha256()),
                refreshToken: Crypt::encrypt(fake()->sha256()),
                expiresIn: Crypt::encrypt((string) 3600),
            ),
            'external_account_id' => fake()->numerify('######'),
            'connected_by' => User::factory(),
            'connected_at' => now(),
            'metadata' => [
                'email' => fake()->unique()->email(),
                'username' => fake()->userName(),
            ],
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
