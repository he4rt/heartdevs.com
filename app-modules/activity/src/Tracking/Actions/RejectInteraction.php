<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Actions;

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Models\Interaction;

final class RejectInteraction
{
    public function handle(Interaction $interaction): Interaction
    {
        $interaction->update([
            'status' => ActivityStatus::Rejected,
            'reviewed_at' => now(),
        ]);

        return $interaction->fresh();
    }
}
