<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Models\Interaction;

final class CalculateReward
{
    /**
     * @return array{coins_awarded: int, xp_awarded: int}
     */
    public function handle(Interaction $interaction, ?int $peerReviewBase = null): array
    {
        $engagementFormula = config('activity-tracking.engagement_formula');
        $xpMultiplier = config('activity-tracking.xp_multiplier', 1);

        $metadata = $interaction->metadata ?? [];
        $engagementSnapshot = $metadata['engagement_snapshot'] ?? null;

        if ($engagementSnapshot !== null) {
            $base = $peerReviewBase ?? (int) (($interaction->coins_min + $interaction->coins_max) / 2);

            $reactionsBonus = min(
                ($engagementSnapshot['reactions'] ?? 0) * $engagementFormula['reactions_multiplier'],
                $engagementFormula['reactions_cap']
            );

            $bookmarksBonus = min(
                ($engagementSnapshot['bookmarks'] ?? 0) * $engagementFormula['bookmarks_multiplier'],
                $engagementFormula['bookmarks_cap']
            );

            $commentsBonus = min(
                ($engagementSnapshot['comments'] ?? 0) * $engagementFormula['comments_multiplier'],
                $engagementFormula['comments_cap']
            );

            $engagementBonus = (int) ($reactionsBonus + $bookmarksBonus + $commentsBonus);
            $coinsAwarded = min($base + $engagementBonus, $interaction->coins_max);
        } else {
            $coinsAwarded = $peerReviewBase !== null
                ? min($peerReviewBase, $interaction->coins_max)
                : $interaction->coins_min;
        }

        $xpAwarded = (int) ($coinsAwarded * $xpMultiplier);

        $interaction->update([
            'coins_awarded' => $coinsAwarded,
            'xp_awarded' => $xpAwarded,
        ]);

        return [
            'coins_awarded' => $coinsAwarded,
            'xp_awarded' => $xpAwarded,
        ];
    }
}
