<?php

declare(strict_types=1);

namespace He4rt\Events\Database\Factories;

use He4rt\Events\Models\Sponsor;
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
            'name' => fake()->name(),
            'homepage_url' => fake()->url(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
