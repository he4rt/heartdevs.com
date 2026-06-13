<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;

class EventsByTypeChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::twitch.dashboard.events_by_type');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $tenantId = filament()->getTenant()?->getKey();

        $counts = TwitchEventLog::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'event_type');

        $palette = [
            '#9146FF', '#00C2A8', '#FF6B6B', '#FFD93D',
            '#6BCB77', '#4D96FF', '#FF922B', '#845EF7',
            '#20C997', '#F06595',
        ];

        $labels = [];
        $data = [];
        $colors = [];

        $index = 0;

        foreach ($counts as $type => $count) {
            $labels[] = sprintf('%s (%d)', $type, $count);
            $data[] = $count;
            $colors[] = $palette[$index % count($palette)];
            $index++;
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
