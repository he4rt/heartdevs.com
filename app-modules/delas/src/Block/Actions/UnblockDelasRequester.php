<?php

declare(strict_types=1);

namespace He4rt\Delas\Block\Actions;

use He4rt\Delas\Block\Events\DelasRequesterUnblocked;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Encerra um bloqueio. A moderadora desfaz os que ela mesma fez; líderes e
 * super admins desfazem qualquer um. O motivo é obrigatório.
 */
final readonly class UnblockDelasRequester
{
    public function __construct(
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(DelasRequesterBlock $block, User $actor, ?string $reason): DelasRequesterBlock
    {
        Gate::forUser($actor)->authorize('moderate-delas');

        $reason = Reason::required($reason);

        throw_unless(
            $block->blocked_by === $actor->getKey() || Gate::forUser($actor)->allows('lead-delas'),
            DelasException::cannotUnblockOthers(),
        );

        $lifted = DB::transaction(fn (): DelasRequesterBlock => $this->liftInTransaction($block, $actor, $reason));

        event(new DelasRequesterUnblocked($lifted->getKey(), $lifted->user_id, $actor->getKey()));

        return $lifted;
    }

    /**
     * Para quem já está numa transação, como a concessão direta a alguém
     * bloqueado. Não checa permissão: quem chama já checou.
     *
     * @throws DelasException
     */
    public function liftInTransaction(DelasRequesterBlock $block, User $actor, string $reason): DelasRequesterBlock
    {
        $locked = DelasRequesterBlock::query()->whereKey($block->getKey())->lockForUpdate()->firstOrFail();

        throw_unless($locked->isActive(), DelasException::blockAlreadyLifted());

        $locked->update([
            'lifted_by' => $actor->getKey(),
            'lifted_at' => now(),
            'lift_reason' => $reason,
        ]);

        $this->recordTransition->handle(
            action: DelasAction::Unblocked,
            subject: $locked->user,
            actor: $actor,
            block: $locked,
            reason: $reason,
        );

        return $locked;
    }
}
