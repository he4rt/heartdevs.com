<?php

declare(strict_types=1);

namespace He4rt\Activity\Reaction\DTOs;

use He4rt\Activity\Reaction\Enums\TimelineReaction;

final readonly class ReactWithDTO
{
    public function __construct(
        public string $userId,
        public string $timelineId,
        public TimelineReaction $reaction,
    ) {}
}
