<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\Platform;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class CasesByPlatformChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.cases_by_platform');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $start = $this->periodStart();

        $counts = ModerationCase::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('source_platform, count(*) as total')
            ->groupBy('source_platform')
            ->pluck('total', 'source_platform');

        $total = $counts->sum();
        $labels = [];
        $data = [];
        $colors = [];

        foreach (Platform::cases() as $platform) {
            $count = $counts->get($platform->value, 0);

            if ($count === 0) {
                continue;
            }

            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            $labels[] = $platform->getLabel().sprintf(' (%s, %s%%)', $count, $pct);
            $data[] = $count;
            $colors[] = Color::convertToHex($platform->getColor()[500]);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
