<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Contracts;

use He4rt\Activity\Tracking\DTOs\TrackActivityDTO;

interface ActivitySourceContract
{
    /**
     * @return array<TrackActivityDTO>
     */
    public function fetchActivities(): array;
}
