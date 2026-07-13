<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;

class EventsPerDayChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::discord.dashboard.events_per_day');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $events = DiscordEventLog::query()
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
                    'label' => __('panel-admin::discord.dashboard.events_per_day_label'),
                    'data' => $data,
                    'borderColor' => '#782bf1',
                    'backgroundColor' => 'rgba(120, 43, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
