<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions;

use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enums\CaseStatus;

final readonly class RouteCaseAction
{
    public function __construct(
        private HistoryBasedPenaltyAdvisor $advisor,
    ) {}

    public function execute(ModerationCase $case): void
    {
        $scores = $case->ai_scores ?? [];
        $maxScore = blank($scores) ? 0 : max($scores);

        $highPriorityThreshold = config('moderation.thresholds.high_priority', 0.9);

        $priority = (int) ($maxScore * 100);

        if ($maxScore >= $highPriorityThreshold) {
            $priority = min($priority + 10, 100);
        }

        $reportBoost = $case->reports()->count() * 10;
        $priority = min($priority + $reportBoost, 100);

        $suggestedAction = null;

        if ($case->suggested_action === null && $case->author_id && $case->violation_type && $case->severity) {
            $suggestion = $this->advisor->suggest(
                $case->author,
                $case->violation_type,
                $case->severity,
            );
            $suggestedAction = $suggestion->action;
        }

        $case->update([
            'status' => CaseStatus::Pending,
            'priority' => $priority,
            'suggested_action' => $suggestedAction ?? $case->suggested_action,
        ]);
    }
}
