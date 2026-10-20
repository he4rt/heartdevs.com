<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagRejected;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Rejeita uma solicitação pendente. O motivo é interno e abre a espera para
 * um novo pedido.
 */
final readonly class RejectDelasRequest
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(DelasTagRequest $request, User $actor, ?string $reason): DelasTagRequest
    {
        Gate::forUser($actor)->authorize('moderate-delas');

        $reason = Reason::required($reason);

        $rejected = DB::transaction(fn (): DelasTagRequest => $this->rejectInTransaction($request, $actor, $reason));

        event(new DelasTagRejected($rejected->getKey(), $rejected->user_id, $actor->getKey()));

        return $rejected;
    }

    /**
     * Para quem já está numa transação, como o bloqueio, que rejeita a
     * solicitação pendente junto.
     *
     * @throws DelasException
     */
    public function rejectInTransaction(DelasTagRequest $request, User $actor, string $reason): DelasTagRequest
    {
        $locked = DelasTagRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

        throw_if($locked->user_id === $actor->getKey(), DelasException::cannotDecideOwn());
        throw_unless(
            $locked->status->canTransitionTo(DelasRequestStatus::Rejected),
            DelasException::invalidTransition($locked->status, DelasRequestStatus::Rejected),
        );

        $locked->update([
            'status' => DelasRequestStatus::Rejected,
            'decided_by' => $actor->getKey(),
            'decided_at' => now(),
            'decision_reason' => $reason,
        ]);

        $this->recordTransition->handle(
            action: DelasAction::Rejected,
            subject: $locked->user,
            actor: $actor,
            request: $locked,
            from: DelasRequestStatus::Pending,
            to: DelasRequestStatus::Rejected,
            reason: $reason,
        );

        return $locked;
    }
}
