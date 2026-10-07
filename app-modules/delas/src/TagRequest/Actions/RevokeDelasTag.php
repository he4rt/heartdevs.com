<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagRevoked;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma líder ou super admin remove a tag. O registro não é apagado: muda para
 * `revoked` e abre a espera para um novo pedido.
 */
final readonly class RevokeDelasTag
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(User $target, User $actor, ?string $reason): DelasTagRequest
    {
        Gate::forUser($actor)->authorize('lead-delas');

        throw_if($target->is($actor), DelasException::cannotDecideOwn());

        $reason = Reason::required($reason);

        $revoked = DB::transaction(function () use ($target, $actor, $reason): DelasTagRequest {
            $approved = DelasTagRequest::query()
                ->where('user_id', $target->getKey())
                ->where('status', DelasRequestStatus::Approved)
                ->lockForUpdate()
                ->first();

            throw_unless($approved instanceof DelasTagRequest, DelasException::hasNoTag());

            $approved->update([
                'status' => DelasRequestStatus::Revoked,
                'decided_by' => $actor->getKey(),
                'decided_at' => now(),
                'decision_reason' => $reason,
            ]);

            $this->recordTransition->handle(
                action: DelasAction::Revoked,
                subject: $target,
                actor: $actor,
                request: $approved,
                from: DelasRequestStatus::Approved,
                to: DelasRequestStatus::Revoked,
                reason: $reason,
            );

            return $approved;
        });

        event(new DelasTagRevoked($revoked->getKey(), $target->getKey(), $actor->getKey()));

        return $revoked;
    }
}
