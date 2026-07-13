<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Queries;

use He4rt\Activity\Timeline\Timeline;
use Illuminate\Database\Eloquent\Builder;

final readonly class TimelineFeed
{
    /** @return Builder<Timeline> */
    public function builder(): Builder
    {
        return Timeline::query()
            ->where('is_ignored', operator: false)
            ->whereHas('user')
            ->whereNull('parent_id')->latest();
    }
}
