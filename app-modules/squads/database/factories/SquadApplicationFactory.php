<?php

declare(strict_types=1);

namespace He4rt\Squads\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\ApplicationStatus;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SquadApplication>
 */
final class SquadApplicationFactory extends Factory
{
    protected $model = SquadApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'squad_id' => Squad::factory(),
            'user_id' => User::factory(),
            'status' => ApplicationStatus::Pending,
            'message' => fake()->sentence(),
            'decided_by' => null,
            'decided_at' => null,
        ];
    }
}
