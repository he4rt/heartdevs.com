<?php

declare(strict_types=1);

namespace App\Support\Activitylog;

use Spatie\Activitylog\Models\Activity;

/**
 * Runs after an activity is stored: scores its risk, then signs the stored row.
 */
final readonly class ActivityAudit
{
    public function __construct(private ActivityIntegrity $integrity) {}

    public function stamp(Activity $activity): void
    {
        $activity->refresh();

        $score = ActivityRisk::score($activity);
        $activity->setAttribute('risk_score', $score);
        $activity->setAttribute('risk_level', ActivityRisk::level($score));
        $activity->setAttribute('integrity_hash', $this->integrity->hash($activity));

        $activity->newQueryWithoutScopes()
            ->whereKey($activity->getKey())
            ->toBase()
            ->update($activity->only(['risk_score', 'risk_level', 'integrity_hash']));
    }
}
