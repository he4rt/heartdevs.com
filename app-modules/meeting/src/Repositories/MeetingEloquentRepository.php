<?php

declare(strict_types=1);

namespace He4rt\Meeting\Repositories;

use He4rt\Meeting\Contracts\MeetingRepository;
use He4rt\Meeting\DTO\NewMeetingDTO;
use He4rt\Meeting\Entities\MeetingEntity;
use He4rt\Meeting\Models\Meeting;
use He4rt\Shared\Contract\Paginator;
use He4rt\Shared\Paginator as PaginatorConcrete;

final readonly class MeetingEloquentRepository implements MeetingRepository
{
    public function __construct(private Meeting $model) {}

    public function paginate(array $relations = [], int $perPage = 10): Paginator
    {
        $meetings = $this->model->newQuery()->with($relations)->paginate($perPage);

        return PaginatorConcrete::paginate($meetings);
    }

    public function create(NewMeetingDTO $dto, string $adminId): MeetingEntity
    {
        $meeting = $this->model->newQuery()->create([
            'tenant_id' => request()->input('tenant_id'),
            'meeting_type_id' => $dto->meetingTypeId,
            'admin_id' => $adminId,
            'starts_at' => now(),
        ]);

        return MeetingEntity::make($meeting->toArray());
    }

    public function endMeeting(string $meetingId): MeetingEntity
    {
        $this->model
            ->newQuery()
            ->find($meetingId)
            ->update(['ends_at' => now()]);

        $meeting = $this->model
            ->newQuery()
            ->find($meetingId);

        return MeetingEntity::make($meeting->toArray());
    }

    public function attendMeeting(string $meetingId, string $userId): void
    {
        $this->model->newQuery()
            ->find($meetingId)
            ->members()
            ->attach($userId, ['attend_at' => now()]);
    }
}
