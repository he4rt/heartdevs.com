<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class ReplyData
{
    public function __construct(
        public string $replyToProviderMessageId,
    ) {}
}
