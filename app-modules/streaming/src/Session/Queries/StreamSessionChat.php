<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Queries;

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Streaming\Session\Models\StreamSession;
use Illuminate\Database\Eloquent\Builder;

final class StreamSessionChat
{
    /**
     * @return array<int, array{username: string, messages: int}>
     */
    public function topChatters(StreamSession $session, int $limit): array
    {
        $ranking = $this->messagesOf($session)
            ->whereNotNull('external_identity_id')
            ->groupBy('external_identity_id')
            ->toBase()
            ->selectRaw('external_identity_id, count(*) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $identities = ExternalIdentity::query()->whereKey($ranking->pluck('external_identity_id'))->get()->keyBy('id');

        return $ranking->map(function (object $row) use ($identities): array {
            $identityId = $row->external_identity_id ?? null;
            $total = $row->total ?? null;
            $identity = is_string($identityId) ? $identities->get($identityId) : null;
            $username = $identity?->metadata['username'] ?? $identity?->external_account_id;

            return [
                'username' => is_string($username) ? $username : '?',
                'messages' => is_numeric($total) ? (int) $total : 0,
            ];
        })->all();
    }

    public function messagesPerMinute(StreamSession $session, int $minutes): float
    {
        $recent = $this->messagesOf($session)->where('sent_at', '>=', now()->subMinutes($minutes))->count();

        return round($recent / $minutes, 1);
    }

    /**
     * @return Builder<Message>
     */
    private function messagesOf(StreamSession $session): Builder
    {
        return Message::query()
            ->where('platform', $session->identity->provider)
            ->where('channel_id', $session->identity->external_account_id)
            ->where('sent_at', '>=', $session->started_at)
            ->where('sent_at', '<=', $session->ended_at ?? now());
    }
}
