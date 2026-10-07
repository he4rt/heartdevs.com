<?php

declare(strict_types=1);

namespace He4rt\Delas\Block\Actions;

use He4rt\Delas\Block\Events\DelasRequesterBlocked;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\Support\Reason;
use He4rt\Delas\TagRequest\Actions\RejectDelasRequest;
use He4rt\Delas\TagRequest\Events\DelasTagRejected;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Impede uma pessoa de solicitar a tag. Rejeita, na mesma transação, a
 * solicitação pendente que ela tiver.
 */
final readonly class BlockDelasRequester
{
    public function __construct(
        private RejectDelasRequest $rejectRequest,
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DelasException
     */
    public function handle(User $target, User $actor, ?string $reason): DelasRequesterBlock
    {
        Gate::forUser($actor)->authorize('moderate-delas');

        $reason = Reason::required($reason);

        // Ninguém da equipe (nem super admin) é bloqueado, nem a si mesma.
        throw_if(
            $target->is($actor) || Gate::forUser($target)->allows('moderate-delas'),
            DelasException::cannotBlockTeam(),
        );

        try {
            [$block, $rejected] = DB::transaction(function () use ($target, $actor, $reason): array {
                $pending = DelasTagRequest::query()->where('user_id', $target->getKey())->pending()->first();
                $rejected = $pending instanceof DelasTagRequest
                    ? $this->rejectRequest->rejectInTransaction($pending, $actor, $reason)
                    : null;

                $block = DelasRequesterBlock::query()->create([
                    'user_id' => $target->getKey(),
                    'blocked_by' => $actor->getKey(),
                    'reason' => $reason,
                    'blocked_at' => now(),
                ]);

                $this->recordTransition->handle(
                    action: DelasAction::Blocked,
                    subject: $target,
                    actor: $actor,
                    block: $block,
                    reason: $reason,
                );

                return [$block, $rejected];
            });
        } catch (UniqueConstraintViolationException) {
            throw DelasException::alreadyBlocked();
        }

        if ($rejected instanceof DelasTagRequest) {
            event(new DelasTagRejected($rejected->getKey(), $target->getKey(), $actor->getKey()));
        }

        event(new DelasRequesterBlocked($block->getKey(), $target->getKey(), $actor->getKey()));

        return $block;
    }
}
