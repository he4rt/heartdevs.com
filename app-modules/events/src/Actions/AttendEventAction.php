<?php

declare(strict_types=1);

namespace He4rt\Events\Actions;

use Exception;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\Pivot\EventAttend;

final class AttendEventAction
{
    /**
     * @throws Exception
     */
    public function execute(EventModel $eventModel): void
    {
        /** @var EventAttend|null $pivot */
        $pivot = $eventModel->attendees()->first()?->pivot;
        $attendingStatus = $pivot->status;
        $eventModel->attend(auth()->user()->id, $attendingStatus);
    }
}
