<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\ValueObjects;

use He4rt\Activity\Voice\Enums\VoicePresenceEnum;

/**
 * A single presence change within one voice-state update: a user joined or
 * left a specific channel. A channel move produces two of these (left old,
 * joined new). The channel name is paired with the presence at derivation
 * time — `joined` carries the new channel's name, `left` the old one.
 */
final readonly class VoiceTransition
{
    public function __construct(
        public VoicePresenceEnum $presence,
        public string $channelId,
        public string $channelName,
    ) {}

    public static function joined(string $channelId, string $channelName): self
    {
        return new self(VoicePresenceEnum::Joined, $channelId, $channelName);
    }

    public static function left(string $channelId, string $channelName): self
    {
        return new self(VoicePresenceEnum::Left, $channelId, $channelName);
    }
}
