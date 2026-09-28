<?php

declare(strict_types=1);

namespace He4rt\Squads\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\InvalidSquadRoleTransition;
use He4rt\Squads\Exceptions\NotAnActiveSquadMember;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Policies\SquadPolicy;
use Illuminate\Support\Facades\DB;

/**
 * Records the off-system Member -> SubCaptain result and its inverse,
 * SubCaptain -> Member, in the membership ledger.
 *
 * This action never mutates the captain seat. `AssignCaptain` owns captain
 * assignment and replacement, while `MarkExMember` currently owns vacancy.
 * The governance decision remains off-platform; this action records its result.
 */
final readonly class PromoteToSubCaptain
{
    public function __construct(
        private SquadPolicy $squadPolicy,
        private RecordMembershipEvent $recordMembershipEvent,
    ) {}

    public function handle(User $actor, Squad $squad, User $subject, ?string $reason = null): SquadMember
    {
        $this->squadPolicy->authorizeSubCaptainManagement($actor, $squad);

        return $this->transition(
            squad: $squad,
            subject: $subject,
            actor: $actor,
            expectedRole: SquadRole::Member,
            targetRole: SquadRole::SubCaptain,
            action: MembershipAction::Promote,
            reason: $reason,
        );
    }

    public function demote(User $actor, Squad $squad, User $subject, ?string $reason = null): SquadMember
    {
        $this->squadPolicy->authorizeSubCaptainManagement($actor, $squad);

        return $this->transition(
            squad: $squad,
            subject: $subject,
            actor: $actor,
            expectedRole: SquadRole::SubCaptain,
            targetRole: SquadRole::Member,
            action: MembershipAction::Demote,
            reason: $reason,
        );
    }

    private function transition(
        Squad $squad,
        User $subject,
        User $actor,
        SquadRole $expectedRole,
        SquadRole $targetRole,
        MembershipAction $action,
        ?string $reason,
    ): SquadMember {
        return DB::transaction(function () use (
            $squad,
            $subject,
            $actor,
            $expectedRole,
            $targetRole,
            $action,
            $reason,
        ): SquadMember {
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

            $fromRole = $member->role;

            if ($fromRole === $targetRole) {
                return $member;
            }

            throw_if(
                $fromRole !== $expectedRole,
                InvalidSquadRoleTransition::between($fromRole, $targetRole)
            );

            $member->update([
                'role' => $targetRole,
            ]);

            $this->recordMembershipEvent->handle(
                squad: $squad,
                subject: $subject,
                action: $action,
                fromRole: $fromRole,
                toRole: $targetRole,
                actor: $actor,
                reason: $reason,
            );

            return $member->refresh();
        });
    }
}
