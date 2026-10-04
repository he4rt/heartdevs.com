<?php

declare(strict_types=1);

namespace He4rt\Streaming\Broadcasting;

use He4rt\Activity\Message\Models\Message;
use He4rt\Streaming\Chat\Data\ChatBadge;
use He4rt\Streaming\Chat\Data\ChatFragment;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ChatMessageReceived implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Streamer $streamer,
        public string $msgId,
        public ChatMessageMetadata $metadata,
    ) {}

    public static function fromMessage(Streamer $streamer, Message $message): self
    {
        return new self(
            streamer: $streamer,
            msgId: $message->provider_message_id ?? '',
            metadata: ChatMessageMetadata::fromArray($message->metadata ?? []),
        );
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->streamer->overlayChannel())];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array{msgId: string, username: string, color: string|null, badges: list<array<string, string|null>>, fragments: list<array<string, string|null>>}
     */
    public function broadcastWith(): array
    {
        return [
            'msgId' => $this->msgId,
            'username' => $this->metadata->displayName,
            'color' => $this->metadata->color,
            'badges' => array_map(fn (ChatBadge $badge): array => $badge->toBroadcast(), $this->metadata->badges),
            'fragments' => array_map(fn (ChatFragment $fragment): array => $fragment->toBroadcast(), $this->metadata->fragments),
        ];
    }
}
