<?php

declare(strict_types=1);

namespace He4rt\Activity\DTOs;

use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

final readonly class NewVoiceMessageDTO
{
    public function __construct(
        public IdentityProvider $provider,
        public string $externalAccountId,
        public VoiceStatesEnum $voiceState,
        public string $channelName,
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            provider: IdentityProvider::from($payload['provider']),
            externalAccountId: $payload['external_account_id'],
            voiceState: VoiceStatesEnum::from($payload['state']),
            channelName: $payload['channel_name']
        );
    }
}
