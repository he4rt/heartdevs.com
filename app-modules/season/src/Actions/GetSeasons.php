<?php

declare(strict_types=1);

namespace He4rt\Season\Actions;

use He4rt\Season\Collections\SeasonCollection;
use He4rt\Season\Contracts\SeasonRepository;

final readonly class GetSeasons
{
    public function __construct(private SeasonRepository $repository) {}

    public function handle(): SeasonCollection
    {
        return $this->repository->getAll();
    }
}
