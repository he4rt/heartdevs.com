<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;

final class LeaveEventAction
{
    public function execute(EventModel $eventModel): void
    {
        $eventModel->leave(auth()->user()->getKey());
    }
}
