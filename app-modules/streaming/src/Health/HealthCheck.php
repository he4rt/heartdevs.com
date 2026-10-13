<?php

declare(strict_types=1);

namespace He4rt\Streaming\Health;

final readonly class HealthCheck
{
    public function __construct(
        public string $key,
        public HealthStatus $status,
        public string $title,
        public string $detail,
        public ?HealthFix $fix = null,
    ) {}
}
