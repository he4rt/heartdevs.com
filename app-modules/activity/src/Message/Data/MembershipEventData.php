<?php

declare(strict_types=1);

namespace He4rt\Activity\Message\Data;

final readonly class MembershipEventData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $kind,
        public string $occurredAt,
        public array $metadata = [],
    ) {}
}
