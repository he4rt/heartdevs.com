<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Factories;

use He4rt\Gamification\Badge\Models\Badge;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
final class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(IdentityProvider::cases()),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'redeem_code' => fake()->slug(2),
            'active' => true,
        ];
    }
}
