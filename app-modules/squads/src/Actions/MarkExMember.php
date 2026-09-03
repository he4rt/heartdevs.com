<?php

declare(strict_types=1);

namespace He4rt\Squads\Actions;

use He4rt\Identity\User\Models\User;
use He4rt\Squads\Enums\MembershipAction;
use He4rt\Squads\Enums\SquadRole;
use He4rt\Squads\Exceptions\NotAnActiveSquadMember;
use He4rt\Squads\Models\Squad;
use He4rt\Squads\Models\SquadMember;
use He4rt\Squads\Policies\SquadPolicy;
use Illuminate\Support\Facades\DB;

/**
 * Closes out a member's standing in a squad, marking them `ExMember`.
 *
 * If the subject holds the captain seat, vacating it needs no extra code —
 * `Squad::captain()` resolves live off the pivot (`where role = captain`),
 * so flipping the row to `ExMember` frees the seat by itself.
 */
final readonly class MarkExMember
{
    public function __construct(
        private SquadPolicy $squadPolicy,
        private RecordMembershipEvent $recordMembershipEvent,
    ) {}

    public function handle(User $actor, Squad $squad, User $subject, ?string $reason = null): SquadMember
    {
        return DB::transaction(function () use ($squad, $subject, $actor, $reason): SquadMember {
            Squad::query()
                ->whereKey($squad->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Squad authority can change while waiting. Check the latest committed role;
            // denied actors briefly hold this lock until the transaction rolls back.
            $this->squadPolicy->authorize($actor, $squad);

            $member = SquadMember::query()
                ->where('squad_id', $squad->id)
                ->where('user_id', $subject->id)
                ->whereNot('role', SquadRole::ExMember)
                ->lockForUpdate()
                ->first();

            throw_if($member === null, NotAnActiveSquadMember::for($squad, $subject));

            $fromRole = $member->role;
            $member->update([
                'role' => SquadRole::ExMember,
                'left_at' => now(),
            ]);

            $this->recordMembershipEvent->handle(
                squad: $squad,
                subject: $subject,
                action: MembershipAction::Leave,
                fromRole: $fromRole,
                toRole: SquadRole::ExMember,
                actor: $actor,
                reason: $reason,
            );

            return $member->refresh();
        });
    }
}
