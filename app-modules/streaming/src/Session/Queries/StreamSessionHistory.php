<?php

declare(strict_types=1);

namespace He4rt\Streaming\Session\Queries;

use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Database\Eloquent\Builder;

/**
 * The sessions of one streamer, with their totals loaded through StreamSessionTotals.
 */
final readonly class StreamSessionHistory
{
    public function __construct(private StreamSessionTotals $totals) {}

    /**
     * @return Builder<StreamSession>
     */
    public function of(Streamer $streamer): Builder
    {
        return $this->ofStreamerId($streamer->id);
    }

    public function live(Streamer $streamer): ?StreamSession
    {
        return $this->of($streamer)->open()->latest('started_at')->first();
    }

    public function lastEnded(Streamer $streamer): ?StreamSession
    {
        return $this->of($streamer)->whereNotNull('ended_at')->latest('started_at')->first();
    }

    public function previous(StreamSession $session): ?StreamSession
    {
        return $this->earlierThan($session)->first();
    }

    public function next(StreamSession $session): ?StreamSession
    {
        return $this->ofStreamerId($session->streamer_id)
            ->where('started_at', '>', $session->started_at)
            ->oldest('started_at')
            ->first();
    }

    /**
     * Skips the sessions without data, so a live that lost its events does not inflate the comparison.
     */
    public function baselineFor(StreamSession $session): ?StreamSession
    {
        return $this->earlierThan($session)
            ->cursor()
            ->first(fn (StreamSession $earlier): bool => !SessionTotals::of($earlier)->hasNoData());
    }

    /**
     * @return Builder<StreamSession>
     */
    private function earlierThan(StreamSession $session): Builder
    {
        return $this->ofStreamerId($session->streamer_id)
            ->where('started_at', '<', $session->started_at)
            ->latest('started_at');
    }

    /**
     * @return Builder<StreamSession>
     */
    private function ofStreamerId(string $streamerId): Builder
    {
        return $this->totals->apply(StreamSession::query()->where('streamer_id', $streamerId));
    }
}
