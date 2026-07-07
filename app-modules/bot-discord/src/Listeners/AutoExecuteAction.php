<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Listeners;

use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;

/**
 * Listens to CaseReadyForEnforcement and auto-executes the suggested action on Discord.
 *
 * This is the "how" side of enforcement for Discord. The moderation module already decided
 * "this case is safe to auto-execute" (deterministic rule match). This listener just creates
 * the action record and dispatches execution.
 *
 * Only acts on Discord-sourced cases. Other platforms register their own listeners.
 */
final class AutoExecuteAction
{
    public function handle(CaseReadyForEnforcement $event): void
    {
        $case = $event->case;

        // Each platform handles its own cases — skip if not Discord.
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
        ]);

        // Async: enforcement runs in the queue (API calls, DM, delete — may fail/retry).
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
