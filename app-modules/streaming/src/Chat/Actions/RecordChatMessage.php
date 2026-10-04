<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use He4rt\Activity\Message\Actions\PersistMessage;
use He4rt\Activity\Message\DTOs\NewMessageDTO;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Broadcasting\ChatMessageReceived;
use He4rt\Streaming\DTOs\IncomingChatMessage;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;
use He4rt\Streaming\Streamer\Models\StreamerSource;

final readonly class RecordChatMessage
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
        private PersistMessage $persistMessage,
    ) {}

    public function handle(IncomingChatMessage $incoming): ?Message
    {
        $source = $this->resolveActiveSource->handle($incoming->platform, $incoming->broadcasterId);

        if (!$source instanceof StreamerSource) {
            return null;
        }

        $chatter = $this->resolveChatterIdentity($incoming);

        $message = $this->persistMessage->handle(
            new NewMessageDTO(
                provider: $incoming->platform,
                providerUsername: $incoming->chatterLogin,
                externalAccountId: $incoming->chatterId,
                providerMessageId: $incoming->providerMessageId,
                channelId: $incoming->broadcasterId,
                content: $incoming->content,
                sentAt: $incoming->sentAt->toDateTimeImmutable(),
            ),
            obtainedExperience: 0,
            providerEntity: $chatter->getKey(),
            metadata: $incoming->metadata->toArray(),
        );

        $isMutedOnOverlay = $source->streamer->settings->mutedChatters->contains($incoming->platform, $incoming->chatterId);

        if ($message->wasRecentlyCreated && $source->showsChat() && !$isMutedOnOverlay) {
            event(ChatMessageReceived::fromMessage($source->streamer, $message));
        }

        return $message;
    }

    private function resolveChatterIdentity(IncomingChatMessage $incoming): ExternalIdentity
    {
        $known = ExternalIdentity::query()
            ->where('provider', $incoming->platform)
            ->where('external_account_id', $incoming->chatterId)
            ->orderByRaw('model_id IS NULL')
            ->first();

        return $known ?? ExternalIdentity::query()->createOrFirst(
            [
                'provider' => $incoming->platform,
                'external_account_id' => $incoming->chatterId,
                'model_id' => null,
            ],
            [
                'model_type' => null,
                'type' => $incoming->platform->getType(),
                'metadata' => ['username' => $incoming->chatterLogin],
            ],
        );
    }
}
