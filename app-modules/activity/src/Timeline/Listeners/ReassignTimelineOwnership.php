<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Listeners;

use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\Auth\Events\AccountsMerged;

/**
 * Reassigns timeline posts and replies from a merged-away account to the
 * surviving account, so an account merge does not orphan the author and
 * preserves the posts under the survivor.
 */
final class ReassignTimelineOwnership
{
    public function handle(AccountsMerged $event): void
    {
        Timeline::query()
            ->where('user_id', $event->mergedId)
            ->update(['user_id' => $event->survivorId]);
    }
}
