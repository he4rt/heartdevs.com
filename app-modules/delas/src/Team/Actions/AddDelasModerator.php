<?php

declare(strict_types=1);

namespace He4rt\Delas\Team\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma líder (ou super admin) dá o papel de moderadora He4rt Delas. Só quem já
 * tem a tag pode moderar: quem modera a He4rt Delas faz parte dela. Líderes e
 * super admins são gerenciados no cadastro de usuários, não aqui.
 */
final readonly class AddDelasModerator
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
        private DelasEligibility $eligibility,
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
        throw_if($target->hasRole(UserRole::DelasModerator), DelasException::alreadyModerator());
        throw_unless($this->eligibility->hasTag($target), DelasException::moderatorNeedsTag());

        DB::transaction(function () use ($target, $actor): void {
            $target->assignRole(UserRole::DelasModerator);

            $this->recordTransition->handle(
                action: DelasAction::ModeratorAdded,
                subject: $target,
                actor: $actor,
            );
        });

        return $target;
    }
}
