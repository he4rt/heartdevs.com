<?php

declare(strict_types=1);

namespace He4rt\Squads\Actions;

use Carbon\CarbonInterface;
use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMembershipEvent;

/**
 * Appends one entry to the squad membership audit trail.
 *
 * Reused by every membership/role mutation in the module. The trail is
 * append-only — this Action only ever creates events, never updates them.
 */
final class RecordMembershipEvent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        Squad $squad,
        User $subject,
        MembershipAction $action,
        ?SquadRole $fromRole = null,
        ?SquadRole $toRole = null,
        ?User $actor = null,
        ?string $reason = null,
        array $metadata = [],
        ?CarbonInterface $occurredAt = null,
    ): SquadMembershipEvent {
        /** @var SquadMembershipEvent $event */
        $event = SquadMembershipEvent::query()->create([
            'tenant_id' => $squad->tenant_id,
            'squad_id' => $squad->id,
            'user_id' => $subject->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_role' => $fromRole,
            'to_role' => $toRole,
            'reason' => $reason,
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);

        return $event;
    }
}
