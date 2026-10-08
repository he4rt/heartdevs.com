<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Block\Actions\BlockDelasRequester;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagRevoked;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Uma líder ou super admin remove a tag. O registro não é apagado: muda para
 * `revoked` e abre a espera para um novo pedido.
 *
 * Quem é da equipe (moderadora ou líder) não perde a tag por aqui: moderadora
 * precisa ter a tag (`AddDelasModerator`), então primeiro a pessoa sai da
 * equipe e só depois pode perder a tag. Assim ninguém modera sem ter a tag.
 *
 * Com `$alsoBlock`, a pessoa também é bloqueada de pedir de novo, com o mesmo
 * motivo e na mesma transação: ou as duas coisas acontecem, ou nenhuma.
 */
final readonly class RevokeDelasTag
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
        private BlockDelasRequester $blockRequester,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(User $target, User $actor, ?string $reason, bool $alsoBlock = false): DelasTagRequest
    {
        Gate::forUser($actor)->authorize('lead-delas');

        throw_if($target->is($actor), DelasException::cannotDecideOwn());
        throw_if(
            $target->hasAnyRole([UserRole::DelasModerator, UserRole::DelasLead]),
            DelasException::teamMemberKeepsTag(),
        );

        $reason = Reason::required($reason);

        return DB::transaction(function () use ($target, $actor, $reason, $alsoBlock): DelasTagRequest {
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

            // Os eventos são ShouldDispatchAfterCommit: só saem se a transação inteira der certo.
            event(new DelasTagRevoked($approved->getKey(), $target->getKey(), $actor->getKey()));

            // Quem já está bloqueada continua com o bloqueio que tem.
            if ($alsoBlock && !resolve(DelasEligibility::class)->isBlocked($target)) {
                $this->blockRequester->handle($target, $actor, $reason);
            }

            return $approved;
        });
    }
}
