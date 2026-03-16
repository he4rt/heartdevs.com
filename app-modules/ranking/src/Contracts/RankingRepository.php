<?php

declare(strict_types=1);

namespace He4rt\Ranking\Contracts;

use App\Contracts\Paginator;

interface RankingRepository
{
    public function rankingByLevel(): Paginator;
}
