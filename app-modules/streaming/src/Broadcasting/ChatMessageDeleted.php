<?php

declare(strict_types=1);

namespace He4rt\Streaming\Broadcasting;

use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageDeleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Streamer $streamer,
        public string $msgId,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'chat.message-deleted';
    }

    /** @return array{msgId: string} */
    public function broadcastWith(): array
    {
        return ['msgId' => $this->msgId];
    }
}
