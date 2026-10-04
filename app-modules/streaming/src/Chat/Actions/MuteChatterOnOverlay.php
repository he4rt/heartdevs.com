<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use Carbon\CarbonImmutable;
use He4rt\Streaming\Broadcasting\ChatterMessagesCleared;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Chat\Queries\StreamerChatMessages;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSettings;
use He4rt\Streaming\Streamer\Data\MutedChatter;
use He4rt\Streaming\Streamer\Models\Streamer;

final readonly class MuteChatterOnOverlay
{
    public function __construct(
        private StreamerChatMessages $chatMessages,
        private UpdateStreamerSettings $updateSettings,
    ) {}

    public function handle(Streamer $streamer, string $messageId, CarbonImmutable $mutedAt): MutedChatter
    {
        $message = $this->chatMessages->of($streamer)->with('provider')->whereKey($messageId)->firstOrFail();

        $chatter = new MutedChatter(
            platform: $message->platform,
            chatterId: $message->provider->external_account_id ?? '',
            displayName: ChatMessageMetadata::fromArray($message->metadata ?? [])->displayName,
            mutedAt: $mutedAt,
        );

        $settings = $streamer->settings;
        $this->updateSettings->handle($streamer, $settings->withMutedChatters($settings->mutedChatters->with($chatter)));

        event(new ChatterMessagesCleared($streamer, $chatter->chatterId));

        return $chatter;
    }
}
