<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Actions;

use He4rt\Community\Meeting\Models\Meeting;
use Illuminate\Support\Facades\Cache;

final readonly class EndMeeting
{
    public function handle(): void
    {
        $meetingId = $this->getAndClearMeetingId();

        Meeting::query()
            ->where('id', $meetingId)
            ->update(['ends_at' => now()]);
    }

    public function getAndClearMeetingId(): string
    {
        $meetingId = Cache::tags(['meetings'])->get('current-meeting');
        $this->clearMeetingCache();

        return $meetingId;
    }

    public function clearMeetingCache(): void
    {
        Cache::tags(['meetings'])->flush();
    }
}
