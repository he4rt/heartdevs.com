<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Listeners;

use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;

final class AutoExecuteAction
{
    public function handle(CaseReadyForEnforcement $event): void
    {
        $case = $event->case;

        if ($case->source_platform !== Platform::Discord) {
            return;
        }

        if ($case->author_id === null) {
            return;
        }

        $action = ModerationAction::query()->create([
            'case_id' => $case->id,
            'moderator_id' => null,
            'action_type' => $case->suggested_action,
            'target_platforms' => [Platform::Discord->value],
            'duration' => $this->resolveDuration($case->suggested_action),
            'reason' => 'Auto-moderation triggered by rule-based classification.',
            'automated' => true,
            'tenant_id' => $case->tenant_id,
        ]);

        dispatch(new ExecuteAction($action, $case->author));
    }

    private function resolveDuration(?ActionType $type): ?string
    {
        return match ($type) {
            ActionType::Ban => 'permanent',
            ActionType::Mute, ActionType::Suspend => '24h',
            default => null,
        };
    }
}
