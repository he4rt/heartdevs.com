<?php

declare(strict_types=1);

namespace He4rt\Streaming\Chat\Actions;

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Broadcasting\ChatterMessagesCleared;
use He4rt\Streaming\Chat\Data\ChatMessageMetadata;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;

final readonly class ClearChatterMessages
{
    private const int LOOKBACK_HOURS = 24;

    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IdentityProvider $platform, string $broadcasterId, string $chatterId, CarbonImmutable $clearedAt): void
    {
        $chatterIdentityId = ExternalIdentity::query()
            ->where('provider', $platform)
            ->where('external_account_id', $chatterId)
            ->value('id');

        if (is_string($chatterIdentityId)) {
            Message::query()
                ->onPlatform($platform)
                ->where('channel_id', $broadcasterId)
                ->where('external_identity_id', $chatterIdentityId)
                ->where('sent_at', '>=', $clearedAt->subHours(self::LOOKBACK_HOURS))
                ->whereNull('metadata->deleted_at')
                ->get()
                ->each(fn (Message $message): bool => $message->update([
                    'metadata' => ChatMessageMetadata::fromArray($message->metadata ?? [])->withDeletedAt($clearedAt)->toArray(),
                ]));
        }

        $source = $this->resolveActiveSource->handle($platform, $broadcasterId);

        if ($source?->showsChat() === true) {
            event(new ChatterMessagesCleared($source->streamer, $chatterId));
        }
    }
}
