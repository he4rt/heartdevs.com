<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Data;

use He4rt\Streaming\Session\Models\StreamSession;

/**
 * Read from a session loaded through StreamSessionTotals.
 */
final readonly class SessionTotals
{
    public function __construct(
        public int $follows = 0,
        public int $subs = 0,
        public int $bits = 0,
        public int $raids = 0,
        public int $events = 0,
        public int $messages = 0,
        public int $chatters = 0,
    ) {}

    public static function of(StreamSession $session): self
    {
        $total = fn (string $attribute): int => (int) $session->getAttribute($attribute);

        return new self(
            follows: $total('follows_total'),
            subs: $total('subs_total'),
            bits: $total('bits_total'),
            raids: $total('raids_total'),
            events: $total('events_total'),
            messages: $total('messages_total'),
            chatters: $total('chatters_total'),
        );
    }

    /**
     * A session with no event and no message most likely means the ingestion stopped, not a quiet live.
     */
    public function hasNoData(): bool
    {
        return $this->events === 0 && $this->messages === 0;
    }
}
