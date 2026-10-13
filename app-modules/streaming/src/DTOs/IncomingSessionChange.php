<?php

declare(strict_types=1);

namespace He4rt\Streaming\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class IncomingSessionChange
{
    public function __construct(
        public IdentityProvider $platform,
        public string $broadcasterId,
        public CarbonImmutable $at,
        public ?string $platformStreamId = null,
        public ?string $title = null,
        public ?string $category = null,
    ) {}
}
