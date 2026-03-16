<?php

declare(strict_types=1);

namespace He4rt\Meeting\Contracts;

use App\Contracts\Paginator;
use He4rt\Meeting\DTO\NewMeetingDTO;
use He4rt\Meeting\Entities\MeetingEntity;

interface MeetingRepository
{
    public function paginate(array $relations = [], int $perPage = 10): Paginator;

    public function create(NewMeetingDTO $dto, string $adminId): MeetingEntity;

    public function endMeeting(string $meetingId): MeetingEntity;

    public function attendMeeting(string $meetingId, string $userId): void;
}
