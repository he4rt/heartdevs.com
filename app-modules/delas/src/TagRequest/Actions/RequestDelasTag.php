<?php

declare(strict_types=1);

namespace He4rt\Delas\TagRequest\Actions;

use He4rt\Delas\Exceptions\DelasException;
use He4rt\Delas\History\Actions\RecordDelasTransition;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\TagRequest\Enums\DelasEligibilityState;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Events\DelasTagRequested;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Delas\TagRequest\Queries\DelasEligibility;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * A própria pessoa pede a tag, depois de confirmar no pop-up.
 */
final readonly class RequestDelasTag
{
    public function __construct(
        private DelasEligibility $eligibility,
        private RecordDelasTransition $recordTransition,
    ) {}

    /**
     * @throws DelasException
     */
    public function handle(User $user): DelasTagRequest
    {
        $eligibility = $this->eligibility->for($user);

        match ($eligibility->state) {
            DelasEligibilityState::Pending, DelasEligibilityState::Member => throw DelasException::alreadyHasActiveRequest(),
            DelasEligibilityState::Blocked => throw DelasException::blocked(),
            DelasEligibilityState::Cooldown => throw DelasException::inCooldown($eligibility->nextAllowedAt ?? now()),
            DelasEligibilityState::DiscordRequired => throw DelasException::discordRequired(),
            DelasEligibilityState::CanRequest => null,
        };

        try {
            $request = DB::transaction(function () use ($user): DelasTagRequest {
                $request = DelasTagRequest::query()->create([
                    'user_id' => $user->getKey(),
                    'status' => DelasRequestStatus::Pending,
                    'requested_at' => now(),
                ]);

                $this->recordTransition->handle(
                    action: DelasAction::Requested,
                    subject: $user,
                    actor: $user,
                    request: $request,
                    to: DelasRequestStatus::Pending,
                );

                return $request;
            });
        } catch (UniqueConstraintViolationException) {
            // Dois pedidos ao mesmo tempo: o índice único parcial deixa só um passar.
            throw DelasException::alreadyHasActiveRequest();
        }

        event(new DelasTagRequested($request->getKey(), $user->getKey()));

        return $request;
    }
}
