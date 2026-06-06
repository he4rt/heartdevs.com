<?php

declare(strict_types=1);

namespace He4rt\Identity\Database\Factories;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/** @extends Factory<Tenant> */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'slug' => fake()->slug(),
            'active' => fake()->boolean(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'owner_id' => User::factory(),
        ];
    }

    public function withOwnerAsMember(): self
    {
        return $this->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($tenant->owner_id));
    }

    public function withDiscordProvider(string $providerId = '123'): self
    {
        return $this->withProvider(IdentityProvider::Discord, $providerId);
    }

    public function withTwitchProvider(string $providerId = '123'): self
    {
        return $this->withProvider(IdentityProvider::Twitch, '456');
    }

    public function withProvider(IdentityProvider $provider = IdentityProvider::Discord, string $providerId = '123'): self
    {
        return $this->afterCreating(function (Tenant $tenant) use ($provider, $providerId): void {
            ExternalIdentity::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'model_type' => (new Tenant)->getMorphClass(),
                'model_id' => $tenant->getKey(),
                'connected_by' => $tenant->owner_id,
                'provider' => $provider,
                'external_account_id' => $providerId,
            ]);
        });
    }
}
