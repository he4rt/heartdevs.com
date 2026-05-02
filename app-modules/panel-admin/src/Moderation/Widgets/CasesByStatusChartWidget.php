<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\PanelAdmin\Moderation\Widgets\Concerns\ResolvesFilterPeriod;

class CasesByStatusChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesFilterPeriod;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.cases_by_status');
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
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $counts->sum();
        $labels = [];
        $data = [];
        $colors = [];

        foreach (CaseStatus::cases() as $status) {
            $count = $counts->get($status->value, 0);
            $pct = $total > 0 ? round(($count / $total) * 100) : 0;
            $labels[] = $status->getLabel().sprintf(' (%s, %s%%)', $count, $pct);
            $data[] = $count;
            $colors[] = Color::convertToHex($status->getColor()[500]);
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
