<?php

declare(strict_types=1);

namespace He4rt\Season\Actions;

use He4rt\Season\Contracts\SeasonRepository;
use He4rt\Season\Entities\SeasonEntity;

final readonly class GetCurrentSeason
{
    public function __construct(private SeasonRepository $repository) {}

    public function handle(): SeasonEntity
    {
        return $this->repository->getCurrent();
    }
}
