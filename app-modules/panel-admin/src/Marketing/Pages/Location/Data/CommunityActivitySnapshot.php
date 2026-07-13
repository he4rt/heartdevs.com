<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Data;

/**
 * Headline community numbers for the location dashboard's stat widget, with the
 * derived trend/coverage values owned here rather than in the presentation layer.
 */
final readonly class CommunityActivitySnapshot
{
    public function __construct(
        public int $webActive,
        public int $webActivePrev,
        public int $discordActive,
        public int $discordActivePrev,
        public int $locatedMembers,
        public int $totalMembers,
        public int $statesReached,
        public int $statesTotal,
    ) {}

    public function webTrend(): float
    {
        return $this->pctDiff($this->webActive, $this->webActivePrev);
    }

    public function discordTrend(): float
    {
        return $this->pctDiff($this->discordActive, $this->discordActivePrev);
    }

    public function coverage(): int
    {
        return $this->totalMembers > 0
            ? (int) round($this->locatedMembers / $this->totalMembers * 100)
            : 0;
    }

    private function pctDiff(int $current, int $previous): float
    {
        if ($previous === 0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
