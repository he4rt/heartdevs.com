<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Sponsors\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Sponsor>
 */
final class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'homepage_url' => fake()->url(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
