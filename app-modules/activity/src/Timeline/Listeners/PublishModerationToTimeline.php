<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Listeners;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Moderation\Enforcement\ActionExecuted;
use He4rt\Moderation\Enums\ActionType;

final class PublishModerationToTimeline
{
    private const array PUBLISHABLE_TYPES = [
        ActionType::Ban,
        ActionType::Kick,
    ];

    public function handle(ActionExecuted $event): void
    {
        $action = $event->action;

        if (!in_array($action->action_type, self::PUBLISHABLE_TYPES, true)) {
            return;
        }

        if ($action->moderator_id === null) {
            return;
        }

        $tenantId = $action->tenant_id ?? $action->case?->tenant_id;

        if ($tenantId === null) {
            return;
        }

        Timeline::query()->create([
            'user_id' => $action->moderator_id,
            'tenant_id' => $tenantId,
            'postable_type' => 'moderation_action',
            'postable_id' => $action->id,
        ]);
    }
}
