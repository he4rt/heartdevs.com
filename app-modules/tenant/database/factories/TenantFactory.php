<?php

declare(strict_types=1);

namespace He4rt\Tenant\Database\Factories;

use He4rt\Provider\Enums\ProviderEnum;
use He4rt\Provider\Models\Provider;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
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

    public function withDiscordProvider(string $providerId = '123'): self
    {
        return $this->withProvider(ProviderEnum::Discord, $providerId);
    }

    public function withTwitchProvider(string $providerId = '123'): self
    {
        return $this->withProvider(ProviderEnum::Twitch, '456');
    }

    public function withProvider(ProviderEnum $provider = ProviderEnum::Discord, string $providerId = '123'): self
    {
        return $this->afterCreating(function (Tenant $tenant) use ($provider, $providerId): void {
            Provider::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);
        });
    }
}
