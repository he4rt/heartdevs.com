<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Actions;

use He4rt\Streaming\Broadcasting\StreamSessionStarted;
use He4rt\Streaming\DTOs\IncomingSessionChange;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Actions\ResolveActiveSource;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\DB;

final readonly class StartStreamSession
{
    public function __construct(
        private ResolveActiveSource $resolveActiveSource,
    ) {}

    public function handle(IncomingSessionChange $change): ?StreamSession
    {
        $source = $this->resolveActiveSource->handle($change->platform, $change->broadcasterId);
        $platformStreamId = $change->platformStreamId;

        if (!$source instanceof StreamerSource || $platformStreamId === null) {
            return null;
        }

        $session = DB::transaction(function () use ($source, $change, $platformStreamId): StreamSession {
            StreamSession::query()
                ->open()
                ->where('external_identity_id', $source->external_identity_id)
                ->where('platform_stream_id', '!=', $platformStreamId)
                ->update(['ended_at' => $change->at]);

            return StreamSession::query()->createOrFirst(
                [
                    'external_identity_id' => $source->external_identity_id,
                    'platform_stream_id' => $platformStreamId,
                ],
                [
                    'streamer_id' => $source->streamer_id,
                    'title' => $change->title,
                    'category' => $change->category,
                    'started_at' => $change->at,
                ],
            );
        });

        if ($session->wasRecentlyCreated) {
            event(new StreamSessionStarted($source, $session));
        }

        return $session;
    }
}
