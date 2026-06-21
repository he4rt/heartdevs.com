<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\ValueObjects;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;

final readonly class VoiceTransition
{
    public function __construct(
        public VoicePresenceEnum $presence,
        public string $channelId,
    ) {}

    public static function joined(string $channelId): self
    {
        return new self(VoicePresenceEnum::Joined, $channelId);
    }

    public static function left(string $channelId): self
    {
        return new self(VoicePresenceEnum::Left, $channelId);
    }
}
