<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Listeners;

use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
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

        if (!in_array($action->action_type, self::PUBLISHABLE_TYPES, strict: true)) {
            return;
        }

        $tenantId = $action->tenant_id ?? $action->case?->tenant_id;

        if ($tenantId === null) {
            return;
        }

        $case = $action->case;
        $subjectIdentityId = $this->resolveIdentity($case?->author_id, $tenantId);
        $moderatorIdentityId = $this->resolveIdentity($action->moderator_id, $tenantId);

        ModerationEvent::query()->create([
            'tenant_id' => $tenantId,
            'external_identity_id' => $subjectIdentityId,
            'moderator_identity_id' => $moderatorIdentityId,
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

    private function resolveIdentity(?string $userId, string $tenantId): ?string
    {
        if ($userId === null) {
            return null;
        }

        return ExternalIdentity::query()
            ->where('model_id', $userId)
            ->where('model_type', (new User)->getMorphClass())
            ->where('tenant_id', $tenantId)
            ->value('id');
    }
}
