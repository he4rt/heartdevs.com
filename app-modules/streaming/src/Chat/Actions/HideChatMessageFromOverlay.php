<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use Carbon\CarbonImmutable;
use He4rt\Streaming\Broadcasting\ChatMessageDeleted;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Chat\Queries\StreamerChatMessages;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class HideChatMessageFromOverlay
{
    public function __construct(
        private StreamerChatMessages $chatMessages,
    ) {}

    public function handle(Streamer $streamer, string $messageId, CarbonImmutable $hiddenAt): void
    {
        $message = $this->chatMessages->of($streamer)->whereKey($messageId)->firstOrFail();

        $metadata = ChatMessageMetadata::fromArray($message->metadata ?? [])->withHiddenAt($hiddenAt);
        $message->update(['metadata' => $metadata->toArray()]);

        event(new ChatMessageDeleted($streamer, $message->provider_message_id ?? ''));
    }
}
