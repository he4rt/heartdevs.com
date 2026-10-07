<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Actions;

use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Enums\DelasTriggeredBy;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;
use He4rt\Identity\User\Models\User;

/**
 * Grava uma linha do histórico. Chamada pelas actions do delas, dentro da
 * transação de cada uma, para o histórico nunca divergir do estado.
 */
final readonly class RecordDelasTransition
{
    public function handle(
        DelasAction $action,
        User $subject,
        ?User $actor,
        ?DelasTagRequest $request = null,
        ?DelasRequesterBlock $block = null,
        ?DelasRequestStatus $from = null,
        ?DelasRequestStatus $to = null,
        ?string $reason = null,
        ?DelasTransition $corrects = null,
    ): DelasTransition {
        return DelasTransition::query()->create([
            'user_id' => $subject->getKey(),
            'request_id' => $request?->getKey(),
            'block_id' => $block?->getKey(),
            'corrects_id' => $corrects?->getKey(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->getKey(),
            'triggered_by' => $actor instanceof User ? DelasTriggeredBy::for($actor) : DelasTriggeredBy::System,
            'reason' => $reason,
        ]);
    }
}
