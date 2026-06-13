<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\IntegrationTwitch\Models\TwitchEventLog;

class EventsPerDayChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::twitch.dashboard.events_per_day');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $tenantId = filament()->getTenant()?->getKey();

        $events = TwitchEventLog::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        for ($day = 0; $day < 30; $day++) {
            $date = $start->copy()->addDays($day);
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $data[] = $events->get($key, 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Events',
                    'data' => $data,
                    'borderColor' => '#9146FF',
                    'backgroundColor' => 'rgba(145, 70, 255, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
