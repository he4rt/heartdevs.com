<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Data;

/**
 * Members aggregated by Brazilian state — the result of {@see \He4rt\PanelAdmin\Marketing\Pages\Location\Queries\MembersByState}.
 */
final readonly class MemberDistribution
{
    /**
     * @param  array<string, int>  $byName  members keyed by accent-insensitive state name (map join key)
     * @param  list<StateShare>  $top  the five states with the most members
     */
    public function __construct(
        public int $total,
        public int $statesReached,
        public int $statesTotal,
        public array $byName,
        public array $top,
    ) {}
}
