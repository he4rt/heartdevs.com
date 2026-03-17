<?php

declare(strict_types=1);

namespace He4rt\Message\Entities;

use He4rt\Gamification\Character\Enums\VoiceStatesEnum;

final class VoiceEntity
{
    public function __construct(
        public int $id,
        public string $providerId,
        public string $channelName,
        public VoiceStatesEnum $voiceState,
        public int $obtainedExperience,
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            id: $payload['id'],
            providerId: $payload['provider_id'],
            channelName: $payload['channel_name'],
            voiceState: VoiceStatesEnum::from($payload['state']),
            obtainedExperience: $payload['obtained_experience']
        );
    }
}
