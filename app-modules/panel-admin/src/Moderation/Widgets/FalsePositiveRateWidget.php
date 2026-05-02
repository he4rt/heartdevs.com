<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class FalsePositiveRateWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    protected function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.false_positive.heading');
    }

    protected function getStats(): array
    {
        $start = $this->periodStart();
        $prevStart = $this->previousPeriodStart();

        $currentRate = $this->fpRate($start, now());
        $previousRate = $this->fpRate($prevStart, $start);

        if ($currentRate < $previousRate) {
            $description = __('panel-admin::moderation.dashboard.false_positive.improving', ['prev' => $previousRate]);
            $descriptionColor = Color::Green;
            $descriptionIcon = Heroicon::MiniArrowTrendingDown;
        } elseif ($currentRate > $previousRate) {
            $description = __('panel-admin::moderation.dashboard.false_positive.worsening', ['prev' => $previousRate]);
            $descriptionColor = Color::Red;
            $descriptionIcon = Heroicon::MiniArrowTrendingUp;
        } else {
            $description = __('panel-admin::moderation.dashboard.false_positive.stable');
            $descriptionColor = Color::Gray;
            $descriptionIcon = Heroicon::MiniMinus;
        }

        $stats = [
            Stat::make(__('panel-admin::moderation.dashboard.false_positive.heading'), $currentRate.'%')
                ->description($description)
                ->descriptionIcon($descriptionIcon)
                ->descriptionColor($descriptionColor)
                ->color($currentRate > 15 ? Color::Red : Color::Green),
        ];

        foreach ([CaseSource::AutoDetect, CaseSource::RuleMatch] as $source) {
            $rate = $this->fpRateBySource($start, now(), $source);
            $stats[] = Stat::make($source->getLabel(), $rate.'%')
                ->description(__('panel-admin::moderation.dashboard.false_positive.fp_suffix'));
        }

        return $stats;
    }

    private function fpRate(mixed $from, mixed $to): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }

    private function fpRateBySource(mixed $from, mixed $to, CaseSource $source): int
    {
        $base = ModerationCase::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('source', $source)
            ->whereIn('status', ['dismissed', 'resolved']);

        $total = (clone $base)->count();
        $dismissed = (clone $base)->where('status', 'dismissed')->count();

        return $total > 0 ? (int) round(($dismissed / $total) * 100) : 0;
    }
}
