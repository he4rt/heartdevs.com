<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;

final class ClassifyActivity
{
    /**
     * @return array{tier: ValueTier, coins_min: int, coins_max: int, status: ActivityStatus}
     */
    public function handle(ActivityType $type): array
    {
        $classification = config('activity-tracking.classification.'.$type->value);

        $tier = ValueTier::from($classification['tier']);
        $autoApproveTiers = config('activity-tracking.auto_approve_tiers', []);

        $status = in_array($tier->value, $autoApproveTiers, strict: true)
            ? ActivityStatus::AutoApproved
            : ActivityStatus::Pending;

        return [
            'tier' => $tier,
            'coins_min' => $classification['coins_min'],
            'coins_max' => $classification['coins_max'],
            'status' => $status,
        ];
    }
}
