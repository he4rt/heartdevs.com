<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Database\Factories;

use Illuminate\Support\Facades\Date;
use He4rt\Sponsors\Models\Sponsor;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'logo_path' => fake()->word(),
            'homepage_url' => fake()->url(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
