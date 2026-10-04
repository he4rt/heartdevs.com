<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Queries;

use He4rt\Activity\Message\Models\Message;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds the totals of each session as subselects, read back with SessionTotals::of().
 * The sub total includes the gifted subs.
 */
final class StreamSessionTotals
{
    /**
     * @param  Builder<StreamSession>  $sessions
     * @return Builder<StreamSession>
     */
    public function apply(Builder $sessions): Builder
    {
        return $sessions->addSelect([
            'follows_total' => $this->events()->selectRaw('count(*)')->where('type', StreamEventType::Follow),
            'subs_total' => $this->events()->selectRaw(
                "count(*) filter (where type = ?) + coalesce(sum((details->>'total')::int) filter (where type = ?), 0)",
                [StreamEventType::Sub->value, StreamEventType::GiftSub->value],
            ),
            'bits_total' => $this->events()->selectRaw("coalesce(sum((details->>'bits')::int), 0)")->where('type', StreamEventType::Cheer),
            'raids_total' => $this->events()->selectRaw('count(*)')->where('type', StreamEventType::Raid),
            'events_total' => $this->events()->selectRaw('count(*)'),
            'messages_total' => $this->messages()->selectRaw('count(*)'),
            'chatters_total' => $this->messages()->selectRaw('count(distinct messages.external_identity_id)'),
        ]);
    }

    /**
     * @return Builder<StreamEvent>
     */
    private function events(): Builder
    {
        return StreamEvent::query()->whereColumn('stream_events.stream_session_id', 'stream_sessions.id');
    }

    /**
     * @return Builder<Message>
     */
    private function messages(): Builder
    {
        return Message::query()
            ->join('external_identities as session_identity', 'session_identity.id', '=', 'stream_sessions.external_identity_id')
            ->whereColumn('messages.platform', 'session_identity.provider')
            ->whereColumn('messages.channel_id', 'session_identity.external_account_id')
            ->whereColumn('messages.sent_at', '>=', 'stream_sessions.started_at')
            ->whereRaw('messages.sent_at <= coalesce(stream_sessions.ended_at, now())');
    }
}
