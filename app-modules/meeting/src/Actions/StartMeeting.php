<?php

declare(strict_types=1);

namespace He4rt\Meeting\Actions;

use He4rt\Meeting\DTO\NewMeetingDTO;
use He4rt\Meeting\Entities\MeetingEntity;
use He4rt\Provider\Actions\FindProvider;
use Illuminate\Support\Facades\Cache;

final readonly class StartMeeting
{
    public function __construct(
        private CreateMeetingAction $createMeetingAction,
        private FindProvider $findProvider,
        private FindMeetingTypeAction $findMeetingType,
    ) {}

    public function handle(string $provider, string $providerId, int $meetingTypeId): MeetingEntity
    {
        $this->findMeetingType->handle($meetingTypeId);

        $meetingDTO = NewMeetingDTO::make($provider, $providerId, $meetingTypeId);
        $providerEntity = $this->findProvider->handle($provider, $providerId);
        $currentMeeting = $this->createMeetingAction->handle($meetingDTO, $providerEntity->modelId);
        $this->setMeetingCache($currentMeeting);

        return $currentMeeting;
    }

    public function setMeetingCache(MeetingEntity $currentMeeting): void
    {
        Cache::tags(['meetings'])->put('current-meeting', $currentMeeting->id, 2 * 3600);
    }
}
