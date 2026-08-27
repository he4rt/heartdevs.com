<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class MentionData
{
    public function __construct(
        public string $mentionedProviderAccountId,
        public int $position,
        public ?string $mentionedUsername = null,
    ) {}
}
