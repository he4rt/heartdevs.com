<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\DTOs;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class NewMeetingDTO
{
    public function __construct(
        public IdentityProvider $provider,
        public string $providerId,
        public int $meetingTypeId,
    ) {}

    public static function make(string $provider, string $providerId, int $meetingTypeId): self
    {
        return new self(
            provider: IdentityProvider::from($provider),
            providerId: $providerId,
            meetingTypeId: $meetingTypeId
        );
    }
}
