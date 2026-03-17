<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Actions;

use He4rt\Community\Meeting\Exceptions\MeetingException;
use He4rt\Community\Meeting\Models\MeetingType;
use Throwable;

final readonly class FindMeetingTypeAction
{
    /**
     * @throws Throwable
     */
    public function handle(int $meetingType): MeetingType
    {
        $meetingTypeModel = MeetingType::query()->find($meetingType);

        throw_unless($meetingTypeModel instanceof MeetingType, MeetingException::meetingTypeNotFound());

        return $meetingTypeModel;
    }
}
