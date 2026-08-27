<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Actions;

use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enums\CaseStatus;

/**
 * Assigns priority and suggests a penalty for a classified case.
 *
 * Priority formula: base (AI score * 100) + high-priority boost (+10) + report boost (reports * 10), capped at 100.
 * Penalty suggestion: only consulted when no action was already set by a rule, uses the author's offense history.
 *
 * Called by both ClassifyAndRoute (rule-match path) and ScreenContent (AI-only path).
 */
final readonly class RouteCaseAction
{
    public function __construct(
        private HistoryBasedPenaltyAdvisor $advisor,
    ) {}

    public function execute(ModerationCase $case): void
    {
        $scores = $case->ai_scores ?? [];
        $maxScore = $scores === [] ? 0 : max($scores);

        $highPriorityThreshold = config('moderation.thresholds.high_priority', 0.9);

        // Base priority: AI confidence mapped to 0-100 scale.
        $priority = (int) ($maxScore * 100);

        if ($maxScore >= $highPriorityThreshold) {
            $priority = min($priority + 10, 100);
        }

        // Community signal: more reports = higher priority.
        $reportBoost = $case->reports()->count() * 10;
        $priority = min($priority + $reportBoost, 100);

        $suggestedAction = null;

        // Only consult the penalty advisor if no rule already set the action.
        // Rules are authoritative — the advisor only fills gaps for AI-flagged cases.
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
