<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Corrige o motivo de uma decisão sem apagar o que foi escrito antes.
 *
 * A correção entra no histórico como uma linha nova (`reason_corrected`) que
 * aponta para a original, e a original continua como foi gravada. O motivo
 * guardado na solicitação ou no bloqueio passa a ser o corrigido, porque é o
 * que vale hoje.
 *
 * Quem escreveu o motivo corrige até `delas.reason_correction_hours` depois;
 * líderes e super admins corrigem a qualquer momento.
 */
final readonly class CorrectDelasReason
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(DelasTransition $original, User $actor, ?string $reason): DelasTransition
    {
        Gate::forUser($actor)->authorize('moderate-delas');

        throw_unless($original->hasCorrectableReason(), DelasException::reasonNotCorrectable());

        $reason = Reason::required($reason);

        $this->ensureCanCorrect($original, $actor);

        return DB::transaction(function () use ($original, $actor, $reason): DelasTransition {
            $locked = DelasTransition::query()->whereKey($original->getKey())->lockForUpdate()->firstOrFail();

            throw_if($locked->currentReason() === $reason, DelasException::reasonUnchanged());

            $correction = $this->recordTransition->handle(
                action: DelasAction::ReasonCorrected,
                subject: $locked->user,
                actor: $actor,
                request: $locked->request,
                block: $locked->block,
                reason: $reason,
                corrects: $locked,
            );

            $this->updateCurrentReason($locked, $reason);

            return $correction;
        });
    }

    /**
     * @throws DelasException
     */
    private function ensureCanCorrect(DelasTransition $original, User $actor): void
    {
        if (Gate::forUser($actor)->allows('lead-delas')) {
            return;
        }

        $isAuthor = $original->actor_id === $actor->getKey();
        $isWithinWindow = $original->reasonCorrectionDeadline()?->isFuture() ?? false;

        throw_unless($isAuthor && $isWithinWindow, DelasException::cannotCorrectReason(config()->integer('delas.reason_correction_hours')));
    }

    /**
     * Leva o motivo corrigido para o registro que a tela mostra hoje, só se esse
     * registro ainda estiver no estado que a decisão criou.
     */
    private function updateCurrentReason(DelasTransition $original, string $reason): void
    {
        match ($original->action) {
            DelasAction::Rejected, DelasAction::Revoked, DelasAction::Granted => $this->updateRequest($original, $reason),
            DelasAction::Blocked => $original->block?->update(['reason' => $reason]),
            DelasAction::Unblocked => $original->block?->update(['lift_reason' => $reason]),
            default => null,
        };
    }

    private function updateRequest(DelasTransition $original, string $reason): void
    {
        $request = $original->request;

        if ($request instanceof DelasTagRequest && $request->status === $original->to_status) {
            $request->update(['decision_reason' => $reason]);
        }
    }
}
