<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\DTOs;

use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class NewVoiceMessageDTO
{
    public function __construct(
        public IdentityProvider $provider,
        public string $externalAccountId,
        public VoiceStatesEnum $voiceState,
        public string $channelName,
        public ?string $channelId = null,
        public ?string $username = null,
    ) {}
}
