<?php

declare(strict_types=1);

namespace He4rt\Squads\Database\Factories;

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMembershipEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SquadMembershipEvent>
 */
final class SquadMembershipEventFactory extends Factory
{
    protected $model = SquadMembershipEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'squad_id' => Squad::factory(),
            'user_id' => User::factory(),
            'actor_id' => null,
            'action' => MembershipAction::Join,
            'from_role' => null,
            'to_role' => SquadRole::Member,
            'reason' => null,
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }
}
