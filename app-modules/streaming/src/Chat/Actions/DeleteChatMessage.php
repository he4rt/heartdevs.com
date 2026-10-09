<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Streaming\Broadcasting\ChatMessageDeleted;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;

final readonly class DeleteChatMessage
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IdentityProvider $platform, string $broadcasterId, string $providerMessageId, CarbonImmutable $deletedAt): void
    {
        $message = Message::query()
            ->onPlatform($platform)
            ->where('channel_id', $broadcasterId)
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if (!$message instanceof Message) {
            return;
        }

        $metadata = ChatMessageMetadata::fromArray($message->metadata ?? [])->withDeletedAt($deletedAt);
        $message->update(['metadata' => $metadata->toArray()]);

        $source = $this->resolveActiveSource->handle($platform, $broadcasterId);

        if ($source?->showsChat() === true) {
            event(new ChatMessageDeleted($source->streamer, $providerMessageId));
        }
    }
}
