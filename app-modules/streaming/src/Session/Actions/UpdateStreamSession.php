<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Actions;

use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;

final readonly class UpdateStreamSession
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IncomingSessionChange $change): ?StreamSession
    {
        $session = $this->resolveActiveSource
            ->handle($change->platform, $change->broadcasterId)
            ?->openSession();

        if (!$session instanceof StreamSession) {
            return null;
        }

        $session->update(array_filter(
            ['title' => $change->title, 'category' => $change->category],
            fn (?string $value): bool => $value !== null,
        ));

        return $session;
    }
}
