<?php

declare(strict_types=1);

namespace He4rt\Squads\Database\Factories;

use He4rt\Squads\Enums\SquadStatus;
use He4rt\Squads\Models\Squad;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Squad>
 */
final class SquadFactory extends Factory
{
    protected $model = Squad::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, asText: true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'objective' => fake()->sentence(),
            'status' => SquadStatus::Draft,
        ];
    }
}
