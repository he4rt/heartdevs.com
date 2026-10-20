<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagApproved;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma moderadora, líder ou super admin aprova uma solicitação pendente.
 */
final readonly class ApproveDelasRequest
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(DelasTagRequest $request, User $actor): DelasTagRequest
    {
        Gate::forUser($actor)->authorize('moderate-delas');

        $approved = DB::transaction(function () use ($request, $actor): DelasTagRequest {
            $locked = DelasTagRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            throw_if($locked->user_id === $actor->getKey(), DelasException::cannotDecideOwn());
            throw_unless(
                $locked->status->canTransitionTo(DelasRequestStatus::Approved),
                DelasException::invalidTransition($locked->status, DelasRequestStatus::Approved),
            );

            $locked->update([
                'status' => DelasRequestStatus::Approved,
                'decided_by' => $actor->getKey(),
                'decided_at' => now(),
            ]);

            $this->recordTransition->handle(
                action: DelasAction::Approved,
                subject: $locked->user,
                actor: $actor,
                request: $locked,
                from: DelasRequestStatus::Pending,
                to: DelasRequestStatus::Approved,
            );

            return $locked;
        });

        event(new DelasTagApproved($approved->getKey(), $approved->user_id, $actor->getKey()));

        return $approved;
    }
}
