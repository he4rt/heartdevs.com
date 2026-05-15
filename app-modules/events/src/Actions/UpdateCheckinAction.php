<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Enums\CheckinStatusEnum;
use He4rt\Events\Models\Pivot\EventAttend;

final class UpdateCheckinAction
{
    public function execute(EventAttend $pivot, int $xpAwarded): void
    {
        $pivot->update([
            'state' => CheckinStatusEnum::Verified->value,
            'verification_method' => 'gps',
            'verified_at' => now(),
            'xp_awarded' => $xpAwarded,
            'streak_multiplier' => 1, // fixo até HE4-47
        ]);
    }
}
