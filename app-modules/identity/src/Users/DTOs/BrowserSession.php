<?php

declare(strict_types=1);

namespace He4rt\Identity\Users\DTOs;

final readonly class BrowserSession
{
    public function __construct(
        public string $browser,
        public string $platform,
        public ?string $ipAddress,
        public string $lastActiveAt,
        public bool $isCurrent,
    ) {}
}
