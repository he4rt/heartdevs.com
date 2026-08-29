<?php

declare(strict_types=1);

namespace He4rt\Live\DTOs;

use Carbon\CarbonImmutable;

/** Estado atual da transmissão segundo a Control API do mediamtx. */
final readonly class LiveStatus
{
    public function __construct(
        public bool $onAir,
        public ?CarbonImmutable $startedAt,
    ) {}

    public static function offline(): self
    {
        return new self(onAir: false, startedAt: null);
    }
}
