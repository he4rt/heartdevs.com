<?php

declare(strict_types=1);

namespace He4rt\Badge\Database\Factories;

use He4rt\Badge\Models\Badge;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'provider' => 'default',
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'image_url' => fake()->imageUrl(),
            'redeem_code' => fake()->slug(2),
            'active' => true,
        ];
    }
}
