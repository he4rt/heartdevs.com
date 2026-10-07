<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Block\Actions\UnblockDelasRequester;
use He4rt\Delas\Block\Events\DelasRequesterUnblocked;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagGranted;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma líder ou super admin dá a tag direto, sem fila e sem espera.
 *
 * Com solicitação pendente, aprova essa solicitação; sem nenhuma, cria uma já
 * aprovada. Se a pessoa estiver bloqueada, o motivo é obrigatório e o
 * bloqueio é encerrado na mesma transação.
 */
final readonly class GrantDelasTag
{
    public function __construct(
        private UnblockDelasRequester $unblock,
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(User $target, User $actor, ?string $reason = null): DelasTagRequest
    {
        Gate::forUser($actor)->authorize('lead-delas');

        throw_if($target->is($actor), DelasException::cannotDecideOwn());

        $activeBlock = DelasRequesterBlock::query()->where('user_id', $target->getKey())->active()->first();
        $reason = $activeBlock instanceof DelasRequesterBlock ? Reason::required($reason) : Reason::optional($reason);

        [$request, $liftedBlock] = DB::transaction(function () use ($target, $actor, $reason, $activeBlock): array {
            $current = DelasTagRequest::query()
                ->where('user_id', $target->getKey())
                ->active()
                ->lockForUpdate()
                ->first();

            throw_if($current?->status === DelasRequestStatus::Approved, DelasException::alreadyHasTag());

            $liftedBlock = $activeBlock instanceof DelasRequesterBlock
                ? $this->unblock->liftInTransaction($activeBlock, $actor, (string) $reason)
                : null;

            $attributes = [
                'status' => DelasRequestStatus::Approved,
                'decided_by' => $actor->getKey(),
                'decided_at' => now(),
                'decision_reason' => $reason,
            ];

            if ($current instanceof DelasTagRequest) {
                $current->update($attributes);
                $request = $current;
            } else {
                $request = DelasTagRequest::query()->create([
                    'user_id' => $target->getKey(),
                    'requested_at' => now(),
                    ...$attributes,
                ]);
            }

            $this->recordTransition->handle(
                action: DelasAction::Granted,
                subject: $target,
                actor: $actor,
                request: $request,
                from: $current instanceof DelasTagRequest ? DelasRequestStatus::Pending : null,
                to: DelasRequestStatus::Approved,
                reason: $reason,
            );

            return [$request, $liftedBlock];
        });

        if ($liftedBlock instanceof DelasRequesterBlock) {
            event(new DelasRequesterUnblocked($liftedBlock->getKey(), $target->getKey(), $actor->getKey()));
        }

        event(new DelasTagGranted($request->getKey(), $target->getKey(), $actor->getKey()));

        return $request;
    }
}
