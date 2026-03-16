<?php

declare(strict_types=1);

namespace He4rt\Meeting\Actions;

use He4rt\Meeting\Exceptions\MeetingException;
use Illuminate\Support\Facades\Cache;

class AttendMeeting
{
    public function __construct(
        private readonly PersistAttendMeetingAction $persistAttendMeeting
    ) {}

    public function handle(string $userId): void
    {
        $meetingId = $this->getMeetingId();

        $this->persistAttendMeeting->handle($meetingId, $userId);
        $userAttendedCacheKey = sprintf('meeting-%s-attended', $userId);
        Cache::tags(['meetings'])->put($userAttendedCacheKey, true, 2 * 3600);
    }

    public function getMeetingId(): string
    {
        throw_unless(Cache::tags(['meetings'])->has('current-meeting'), MeetingException::nonexistentMeeting());

        return Cache::tags(['meetings'])->get('current-meeting');
    }
}
