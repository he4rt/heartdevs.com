<?php

declare(strict_types=1);

namespace He4rt\Moderation\Classification\Jobs;

use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Actions\Advisors\HistoryBasedPenaltyAdvisor;
use He4rt\Moderation\Enums\CaseStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class RouteDecision implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(private readonly ModerationCase $case) {}

    public function handle(): void
    {
        $scores = $this->case->ai_scores ?? [];
        $maxScore = blank($scores) ? 0 : max($scores);

        $flagThreshold = config('moderation.thresholds.flag', 0.7);
        $highPriorityThreshold = config('moderation.thresholds.high_priority', 0.9);

        if ($maxScore < $flagThreshold) {
            $this->case->update(['status' => CaseStatus::Dismissed]);

            return;
        }

        $priority = (int) ($maxScore * 100);

        if ($maxScore >= $highPriorityThreshold) {
            $priority = min($priority + 10, 100);
        }

        $reportBoost = $this->case->reports()->count() * 10;
        $priority = min($priority + $reportBoost, 100);

        $suggestedAction = null;
        if ($this->case->author && $this->case->violation_type && $this->case->severity) {
            $advisor = new HistoryBasedPenaltyAdvisor();
            $suggestion = $advisor->suggest(
                $this->case->author,
                $this->case->violation_type,
                $this->case->severity,
            );
            $suggestedAction = $suggestion->action;
        }

        $this->case->update([
            'status' => CaseStatus::Pending,
            'priority' => $priority,
            'suggested_action' => $suggestedAction,
        ]);
    }
}
