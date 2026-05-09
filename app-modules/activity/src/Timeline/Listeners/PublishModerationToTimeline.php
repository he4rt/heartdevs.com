<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Listeners;

use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
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

        $tenantId = $action->tenant_id ?? $action->case?->tenant_id;

        if ($tenantId === null) {
            return;
        }

        $case = $action->case;

        ModerationEvent::query()->create([
            'tenant_id' => $tenantId,
            'external_identity_id' => $case?->author_id,
            'moderator_identity_id' => $action->moderator_id,
            'type' => ModerationType::from($action->action_type->value),
            'reason' => $action->reason ?? $case?->content_snapshot['text'] ?? null,
            'metadata' => [
                'source' => 'web_panel',
                'case_id' => $case?->id,
                'moderator_visible' => true,
                'action_id' => $action->id,
                'reports_count' => $case?->reports()?->count() ?? 0,
                'violation_type' => $case?->violation_type?->value,
            ],
            'occurred_at' => $action->created_at ?? now(),
        ]);
    }
}
