<?php

declare(strict_types=1);

namespace He4rt\Streaming\Overlay;

use He4rt\Activity\Message\Models\Message;
use He4rt\Streaming\Broadcasting\ChatMessageReceived;
use He4rt\Streaming\Broadcasting\StreamSessionStarted;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Database\Eloquent\Builder;

final readonly class OverlayInitialState
{
    /**
     * @return list<array{msgId: string, username: string, color: string|null, badges: list<array<string, string|null>>, fragments: list<array<string, string|null>>}>
     */
    public function recentChat(Streamer $streamer, int $limit = 30): array
    {
        $chatSources = $streamer->sources()
            ->with('identity')
            ->get()
            ->filter(fn (StreamerSource $source): bool => $source->showsChat());

        if ($chatSources->isEmpty()) {
            return [];
        }

        $latestMessages = Message::query()
            ->where(function (Builder $query) use ($chatSources): void {
                foreach ($chatSources as $source) {
                    $query->orWhere(fn (Builder $channel): Builder => $channel
                        ->where('platform', $source->identity->provider)
                        ->where('channel_id', $source->identity->external_account_id));
                }
            })
            ->whereNull('metadata->deleted_at')
            ->latest('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (Message $message): array => ChatMessageReceived::fromMessage($streamer, $message)->broadcastWith())
            ->all();

        return array_values($latestMessages);
    }

    /**
     * @return array{title: string|null, category: string|null, startedAt: string}|null
     */
    public function openSession(Streamer $streamer): ?array
    {
        $enabledSources = $streamer->sources()->where('enabled', operator: true)->get();

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
}
