<?php

declare(strict_types=1);

namespace He4rt\Squads\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\NotAnActiveSquadMember;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Records the outcome of an off-system captain election.
 *
 * The seat is single: assigning a new captain demotes the incumbent to
 * `Member` in the same transaction, so the partial unique index
 * `UNIQUE (squad_id) WHERE role = 'captain'` never sees two captains.
 *
 * Vacating the seat is not this Action's job — a captain who leaves becomes
 * an `ExMember` (see `MarkExMember`), which frees the seat by itself.
 */
final readonly class AssignCaptain
{
    public function __construct(
        private RecordMembershipEvent $recordMembershipEvent,
    ) {}

    public function handle(User $actor, Squad $squad, User $subject, ?string $reason = null): SquadMember
    {
        throw_unless($actor->isAdmin(), AuthorizationException::class);

        return DB::transaction(function () use ($squad, $subject, $actor, $reason): SquadMember {
            // Captain-seat mutations lock the squad before any membership row.
            Squad::query()
                ->whereKey($squad->id)
                ->lockForUpdate()
                ->firstOrFail();

            $member = SquadMember::query()
                ->where('squad_id', $squad->id)
                ->where('user_id', $subject->id)
                ->whereNot('role', SquadRole::ExMember)
                ->lockForUpdate()
                ->first();

            throw_if($member === null, NotAnActiveSquadMember::for($squad, $subject));

            $incumbent = $squad->captain()
                ->lockForUpdate()
                ->first();

            if ($incumbent?->is($member)) {
                return $member;
            }

            if ($incumbent !== null) {
                $this->demote($actor, $squad, $incumbent, $reason);
            }

            $fromRole = $member->role;
            $member->update([
                'role' => SquadRole::Captain,
            ]);

            $this->recordMembershipEvent->handle(
                squad: $squad,
                subject: $subject,
                action: MembershipAction::CaptainAssigned,
                fromRole: $fromRole,
                toRole: SquadRole::Captain,
                actor: $actor,
                reason: $reason,
            );

            return $member->refresh();
        });
    }

    private function demote(User $actor, Squad $squad, SquadMember $incumbent, ?string $reason): void
    {
        $fromRole = $incumbent->role;

        $incumbent->update([
            'role' => SquadRole::Member,
        ]);

        $this->recordMembershipEvent->handle(
            squad: $squad,
            subject: $incumbent->user,
            action: MembershipAction::Demote,
            fromRole: $fromRole,
            toRole: SquadRole::Member,
            actor: $actor,
            reason: $reason,
        );
    }
}
