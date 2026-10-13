<?php

declare(strict_types=1);

namespace He4rt\Streaming\Broadcasting;

use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StreamSessionStarted implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public StreamerSource $source,
        public StreamSession $session,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->source->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'session.started';
    }

    public function broadcastWhen(): bool
    {
        return $this->source->enabled;
    }

    /** @return array{title: string|null, category: string|null, startedAt: string} */
    public function broadcastWith(): array
    {
        return [
            'title' => $this->session->title,
            'category' => $this->session->category,
            'startedAt' => $this->session->started_at->toIso8601String(),
        ];
    }
}
