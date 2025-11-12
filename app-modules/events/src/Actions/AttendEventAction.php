<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use He4rt\Events\Models\EventModel;

final class AttendEventAction
{
    /**
     * @throws Exception
     */
    public function execute(EventModel $eventModel): void
    {
        $attendingStatus = $eventModel->attendees()->first()->pivot->status;
        $eventModel->attend(auth()->user()->id, $attendingStatus);
    }
}
