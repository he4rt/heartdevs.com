<?php

declare(strict_types=1);

namespace He4rt\Streaming\DTOs;

use Carbon\CarbonImmutable;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Data\StreamEventDetails;

final readonly class IncomingStreamEvent
{
    public function __construct(
        public IdentityProvider $platform,
        public string $broadcasterId,
        public string $sourceEventId,
        public StreamEventType $type,
        public CarbonImmutable $occurredAt,
        public ?StreamActor $actor = null,
        public ?StreamEventDetails $details = null,
    ) {}
}
