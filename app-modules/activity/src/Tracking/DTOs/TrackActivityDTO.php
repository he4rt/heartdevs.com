<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\DTOs;

use DateTimeImmutable;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class TrackActivityDTO
{
    public function __construct(
        public string $characterId,
        public string $tenantId,
        public ActivityType $type,
        public IdentityProvider $provider,
        public DateTimeImmutable $occurredAt,
        public ?string $externalRef = null,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        /** @var array<string, mixed>|null */
        public ?array $metadata = null,
    ) {}
}
