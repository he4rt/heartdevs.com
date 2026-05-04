<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets\Concerns;

use Carbon\Carbon;

trait ResolvesFilterPeriod
{
    protected function periodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };
    }

    protected function previousPeriodStart(): Carbon
    {
        $period = $this->pageFilters['period'] ?? '30d';

        return match ($period) {
            '7d' => now()->subDays(14),
            '90d' => now()->subDays(180),
            'month' => now()->subMonth()->startOfMonth(),
            'year' => now()->subYear()->startOfYear(),
            default => now()->subDays(60),
        };
    }
}
