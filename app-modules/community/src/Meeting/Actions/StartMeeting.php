<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Actions;

use He4rt\Community\Meeting\DTOs\NewMeetingDTO;
use He4rt\Community\Meeting\Models\Meeting;
use He4rt\Identity\ExternalIdentity\Actions\FindExternalIdentity;
use Illuminate\Support\Facades\Cache;

final readonly class StartMeeting
{
    public function __construct(
        private FindExternalIdentity $findExternalIdentity,
        private FindMeetingTypeAction $findMeetingType,
    ) {}

    public function handle(string $provider, string $providerId, int $meetingTypeId): Meeting
    {
        $this->findMeetingType->handle($meetingTypeId);

        $meetingDTO = NewMeetingDTO::make($provider, $providerId, $meetingTypeId);
        $externalIdentity = $this->findExternalIdentity->handle($provider, $providerId);

        $currentMeeting = Meeting::query()->create([
            'tenant_id' => request()->input('tenant_id'),
            'meeting_type_id' => $meetingDTO->meetingTypeId,
            'admin_id' => $externalIdentity->model_id,
            'starts_at' => now(),
        ]);

        $this->setMeetingCache($currentMeeting);

        return $currentMeeting;
    }

    public function setMeetingCache(Meeting $currentMeeting): void
    {
        Cache::tags(['meetings'])->put('current-meeting', $currentMeeting->id, 2 * 3600);
    }
}
