<?php

declare(strict_types=1);

namespace He4rt\Moderation\Audit;

use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Events\CaseResolved;
use He4rt\Moderation\Enforcement\ActionExecuted;

final class RecordAuditLog
{
    public function handleCaseCreated(CaseCreated $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'case_created',
            'actor_id' => null,
            'actor_type' => 'system',
            'case_id' => $event->case->id,
            'details' => [
                'source' => $event->case->source->value,
                'platform' => $event->case->source_platform->value,
                'content_type' => $event->case->content_type,
            ],
            'platform' => $event->case->source_platform->value,
        ]);
    }

    public function handleCaseResolved(CaseResolved $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'case_resolved',
            'actor_id' => $event->case->assigned_to,
            'actor_type' => 'moderator',
            'case_id' => $event->case->id,
            'details' => [
                'status' => $event->case->status->value,
                'violation_type' => $event->case->violation_type?->value,
            ],
            'platform' => $event->case->source_platform->value,
        ]);
    }

    public function handleActionExecuted(ActionExecuted $event): void
    {
        ModerationAuditLog::query()->create([
            'event_type' => 'action_executed',
            'actor_id' => $event->action->moderator_id,
            'actor_type' => $event->action->automated ? 'system' : 'moderator',
            'case_id' => $event->action->case_id,
            'details' => [
                'action_type' => $event->action->action_type->value,
                'target_platforms' => $event->action->target_platforms,
                'duration' => $event->action->duration,
                'execution_results' => $event->action->execution_results,
            ],
            'platform' => null,
        ]);
    }
}
