<?php

declare(strict_types=1);

namespace He4rt\Squads\Database\Factories;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SquadMember>
 */
final class SquadMemberFactory extends Factory
{
    protected $model = SquadMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'squad_id' => Squad::factory(),
            'user_id' => User::factory(),
            'role' => SquadRole::Member,
            'joined_at' => now(),
            'left_at' => null,
        ];
    }
}
