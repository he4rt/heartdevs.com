<?php

declare(strict_types=1);

namespace He4rt\Streaming\Broadcasting;

use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OverlaySourcesUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Streamer $streamer,
        public OverlayScene $scene,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'sources.updated';
    }

    /** @return array{scene: string} */
    public function broadcastWith(): array
    {
        return ['scene' => $this->scene->value];
    }
}
