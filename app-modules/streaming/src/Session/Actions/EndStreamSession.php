<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Actions;

use He4rt\Streaming\Broadcasting\StreamSessionEnded;
use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class EndStreamSession
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IncomingSessionChange $change): ?StreamSession
    {
        $source = $this->resolveActiveSource->handle($change->platform, $change->broadcasterId);
        $session = $source?->openSession();

        if (!$source instanceof StreamerSource || !$session instanceof StreamSession) {
            return null;
        }

        $session->update(['ended_at' => $change->at]);

        event(new StreamSessionEnded($source, $session));

        return $session;
    }
}
