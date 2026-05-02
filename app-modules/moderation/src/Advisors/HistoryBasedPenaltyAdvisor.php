<?php

declare(strict_types=1);

namespace He4rt\Moderation\Advisors;

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Contracts\PenaltyAdvisorContract;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Models\ModerationAction;
use Illuminate\Contracts\Database\Query\Builder;

final class HistoryBasedPenaltyAdvisor implements PenaltyAdvisorContract
{
    public function suggest(User $user, ViolationType $violation, Severity $severity): SuggestedPenaltyDTO
    {
        $windowDays = config('moderation.penalties.escalation_window_days', 30);

        $priorActions = ModerationAction::query()
            ->whereHas('case', fn (Builder $q) => $q
                ->where('author_id', $user->id)
                ->where('status', CaseStatus::Resolved->value)
            )
            ->where('created_at', '>=', now()->subDays($windowDays))->latest()
            ->get();

        $priorOffenses = $priorActions->count();

        $history = $priorActions->map(fn (ModerationAction $action) => [
            'type' => $action->action_type->value,
            'date' => $action->created_at->toDateString(),
            'violation' => $action->case?->violation_type?->value,
        ])->all();

        [$action, $duration] = $this->escalate($priorOffenses, $severity);

        $reasoning = $this->buildReasoning($priorOffenses, $severity, $windowDays);

        return new SuggestedPenaltyDTO(
            action: $action,
            duration: $duration,
            reasoning: $reasoning,
            priorOffenses: $priorOffenses,
            history: $history,
        );
    }

    /** @return array{ActionType, ?string} */
    private function escalate(int $priorOffenses, Severity $severity): array
    {
        if ($priorOffenses >= 5) {
            return [ActionType::Ban, 'permanent'];
        }

        if ($priorOffenses >= 3) {
            return [ActionType::Ban, '30d'];
        }

        if ($priorOffenses >= 2) {
            return [ActionType::Ban, '7d'];
        }

        if ($priorOffenses === 1) {
            return match (true) {
                $severity === Severity::High || $severity === Severity::Critical => [ActionType::Mute, '7d'],
                $severity === Severity::Medium => [ActionType::Mute, '24h'],
                default => [ActionType::Warn, null],
            };
        }

        return match (true) {
            $severity === Severity::High || $severity === Severity::Critical => [ActionType::Mute, '24h'],
            default => [ActionType::Warn, null],
        };
    }

    private function buildReasoning(int $priorOffenses, Severity $severity, int $windowDays): string
    {
        if ($priorOffenses === 0) {
            return sprintf('First offense (%s severity). No prior actions in last %d days.', $severity->value, $windowDays);
        }

        return sprintf('%s prior offense(s) in last %d days. Severity: %s.', $priorOffenses, $windowDays, $severity->value);
    }
}
