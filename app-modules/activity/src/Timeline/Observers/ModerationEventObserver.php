<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\Observers;

use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Timeline\Actions\PublishModerationEntry;

final readonly class ModerationEventObserver
{
    public function __construct(
        private PublishModerationEntry $publishModerationEntry,
    ) {}

    public function created(ModerationEvent $event): void
    {
        $this->publishModerationEntry->handle($event);
    }
}
