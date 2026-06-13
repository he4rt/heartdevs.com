<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Queries;

use He4rt\Activity\Timeline\Timeline;
use Illuminate\Database\Eloquent\Builder;

final readonly class TimelineFeed
{
    public function __construct(
        private string $tenantId,
    ) {}

    /** @return Builder<Timeline> */
    public function builder(): Builder
    {
        return Timeline::query()
            ->where('tenant_id', $this->tenantId)
            ->where('is_ignored', false)
            ->whereNull('parent_id')->latest();
    }
}
