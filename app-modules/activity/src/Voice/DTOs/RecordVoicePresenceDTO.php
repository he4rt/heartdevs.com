<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\DTOs;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class RecordVoicePresenceDTO
{
    public function __construct(
        public string $tenantId,
        public IdentityProvider $provider,
        public string $externalAccountId,
        public VoicePresenceEnum $presence,
        public string $channelName,
        public ?string $channelId = null,
        public ?string $username = null,
    ) {}
}
