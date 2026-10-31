<?php

declare(strict_types=1);

namespace He4rt\Streaming\Overlay;

use He4rt\Activity\Message\Models\Message;
use He4rt\Streaming\Broadcasting\ChatMessageReceived;
use He4rt\Streaming\Broadcasting\StreamSessionStarted;
use He4rt\Streaming\Chat\Queries\StreamerChatMessages;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Builder;

final readonly class OverlayInitialState
{
    public function __construct(
        private StreamerChatMessages $chatMessages,
    ) {}

    /**
     * @param  list<string>|null  $sourceIds  narrows to these sources; null keeps every source
     * @return list<array{sourceId: string|null, msgId: string, chatterId: string, username: string, color: string|null, badges: list<array<string, string|null>>, fragments: list<array<string, string|null>>}>
     */
    public function recentChat(Streamer $streamer, int $limit = 30, ?array $sourceIds = null): array
    {
        $sourceIdByChannel = $this->chatMessages->chatSources($streamer, $sourceIds)
            ->mapWithKeys(fn (StreamerSource $source): array => [$this->channelKey($source->identity->provider->value, $source->identity->external_account_id) => $source->id])
            ->all();

        $latestMessages = $this->chatMessages->onOverlay($streamer, $sourceIds)
            ->with('provider')
            ->latest('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (Message $message): array => ChatMessageReceived::fromMessage(
                $streamer,
                $message,
                $sourceIdByChannel[$this->channelKey($message->platform->value, (string) $message->channel_id)] ?? null,
            )->broadcastWith())
            ->all();

        return array_values($latestMessages);
    }

    /**
     * @param  list<string>|null  $sourceIds  narrows to these sources; null keeps every source
     * @return array{sourceId: string, title: string|null, category: string|null, startedAt: string}|null
     */
    public function openSession(Streamer $streamer, ?array $sourceIds = null): ?array
    {
        $enabledSources = $streamer->sources()
            ->where('enabled', operator: true)
            ->when($sourceIds !== null, fn (Builder $query): Builder => $query->whereKey($sourceIds))
            ->get();

        $session = StreamSession::query()
            ->open()
            ->whereIn('external_identity_id', $enabledSources->pluck('external_identity_id'))
            ->latest('started_at')
            ->first();

        $source = $enabledSources->firstWhere('external_identity_id', $session?->external_identity_id);

        if (!$session instanceof StreamSession || !$source instanceof StreamerSource) {
            return null;
        }

        return new StreamSessionStarted($source, $session)->broadcastWith();
    }

    private function channelKey(string $platform, string $channelId): string
    {
        return $platform.':'.$channelId;
    }
}
