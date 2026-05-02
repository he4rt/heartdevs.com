<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class TopViolationTypesChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.top_violations');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: { display: false },
                    y: { grid: { display: false } },
                },
            }
        JS);
    }

    protected function getData(): array
    {
        $start = $this->periodStart();

        $counts = ModerationCase::query()
            ->where('created_at', '>=', $start)
            ->whereNotNull('violation_type')
            ->selectRaw('violation_type, count(*) as total')
            ->groupBy('violation_type')
            ->orderByDesc('total')
            ->pluck('total', 'violation_type');

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($counts as $value => $count) {
            $type = ViolationType::from($value);
            $labels[] = $type->getLabel();
            $data[] = $count;
            $colors[] = Color::convertToHex($type->getColor()[500]);
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
