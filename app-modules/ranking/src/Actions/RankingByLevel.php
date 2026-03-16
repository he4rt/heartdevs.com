<?php

declare(strict_types=1);

namespace He4rt\Ranking\Actions;

use App\Contracts\Paginator;
use He4rt\Ranking\Contracts\RankingRepository;

final readonly class RankingByLevel
{
    public function __construct(
        private RankingRepository $rankingRepository
    ) {}

    public function handle(): Paginator
    {
        return $this->rankingRepository->rankingByLevel();
    }
}
