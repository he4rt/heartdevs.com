<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class ThreadData
{
    public function __construct(
        public string $providerThreadId,
        public ?string $name = null,
        public ?bool $archived = null,
        public ?int $autoArchiveDuration = null,
    ) {}
}
