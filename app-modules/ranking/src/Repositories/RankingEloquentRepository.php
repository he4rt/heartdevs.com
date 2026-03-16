<?php

declare(strict_types=1);

namespace He4rt\Ranking\Repositories;

use App\Contracts\Paginator;
use App\Support\Paginator as PaginatorConcrete;
use He4rt\Character\Models\Character;
use He4rt\Ranking\Contracts\RankingRepository;

final class RankingEloquentRepository implements RankingRepository
{
    public function rankingByLevel(): Paginator
    {
        $ranking = Character::with(['user'])
            ->orderByDesc('experience')
            ->paginate(10);

        return PaginatorConcrete::paginate($ranking);
    }
}
