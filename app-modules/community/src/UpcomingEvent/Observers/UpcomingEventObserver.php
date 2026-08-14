<?php

declare(strict_types=1);

namespace He4rt\Community\UpcomingEvent\Observers;

use Carbon\CarbonInterface;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;

final readonly class UpcomingEventObserver
{
    public function saving(UpcomingEvent $event): void
    {
        if ($event->skip_until instanceof CarbonInterface && $event->skip_until->isPast()) {
            $event->skip_next_occurrence = false;
            $event->skip_until = null;

            return;
        }

        if (!$event->skip_next_occurrence) {
            $event->skip_until = null;

            return;
        }

        if ($event->skip_until === null) {
            $event->skip_until = $event->nextOccurrence();
        }
    }
}
