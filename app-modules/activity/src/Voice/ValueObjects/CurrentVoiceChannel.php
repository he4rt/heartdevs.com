<?php

declare(strict_types=1);

namespace He4rt\Activity\Voice\ValueObjects;

/**
 * The voice channel a user is currently in, as derived from the append-only
 * voice_messages log (our own durable source of truth — not discord-php's
 * in-memory cache, which resets on every gateway reconnect).
 */
final readonly class CurrentVoiceChannel
{
    public function __construct(
        public string $channelId,
        public ?string $channelName,
    ) {}
}
