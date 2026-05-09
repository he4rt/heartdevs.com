<?php

declare(strict_types=1);

namespace He4rt\Activity\Timeline\DTOs;

final readonly class CreateReplyDTO
{
    public function __construct(
        public string $userId,
        public string $parentTimelineId,
        public string $content,
    ) {}
}
