<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Actions;

use He4rt\Community\Meeting\Exceptions\MeetingException;
use He4rt\Community\Meeting\Models\Meeting;
use Illuminate\Support\Facades\Cache;

final readonly class AttendMeeting
{
    public function handle(string $userId): void
    {
        $meetingId = $this->getMeetingId();

        Meeting::query()
            ->where('id', $meetingId)
            ->firstOrFail()
            ->members()
            ->attach($userId, ['attend_at' => now()]);

        $userAttendedCacheKey = sprintf('meeting-%s-attended', $userId);
        Cache::tags(['meetings'])->put($userAttendedCacheKey, true, 2 * 3_600);
    }

    public function getMeetingId(): string
    {
        throw_unless(Cache::tags(['meetings'])->has('current-meeting'), MeetingException::nonexistentMeeting());

        return Cache::tags(['meetings'])->get('current-meeting');
    }
}
