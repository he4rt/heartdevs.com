<?php

declare(strict_types=1);

namespace He4rt\Meeting\Actions;

use App\Contracts\Paginator;
use He4rt\Meeting\Contracts\MeetingRepository;

final readonly class PaginateMeetingsAction
{
    public function __construct(private MeetingRepository $repository) {}

    public function handle(): Paginator
    {
        return $this->repository->paginate(['meetingType']);
    }
}
