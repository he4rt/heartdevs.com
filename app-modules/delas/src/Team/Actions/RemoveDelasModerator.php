<?php

declare(strict_types=1);

namespace He4rt\Delas\Team\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma líder (ou super admin) retira o papel de moderadora He4rt Delas. As
 * decisões que ela tomou continuam no histórico.
 */
final readonly class RemoveDelasModerator
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(User $target, User $actor): User
    {
        Gate::forUser($actor)->authorize('lead-delas');

        throw_if(
            $target->isSuperAdmin() || $target->hasRole(UserRole::DelasLead),
            DelasException::cannotManageRole(),
        );
        throw_unless($target->hasRole(UserRole::DelasModerator), DelasException::notModerator());

        DB::transaction(function () use ($target, $actor): void {
            $target->removeRole(UserRole::DelasModerator);

            $this->recordTransition->handle(
                action: DelasAction::ModeratorRemoved,
                subject: $target,
                actor: $actor,
            );
        });

        return $target;
    }
}
