<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use He4rt\IntegrationDiscord\Models\DiscordMember;

class MemberGrowthChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::discord.dashboard.member_growth.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();

        $joins = DiscordMember::query()
            ->where('joined_at', '>=', $start)
            ->selectRaw('DATE(joined_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $leaves = DiscordMember::query()
            ->where('left_at', '>=', $start)
            ->selectRaw('DATE(left_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $joinsData = [];
        $leavesData = [];

        for ($day = 0; $day < 30; $day++) {
            $date = $start->copy()->addDays($day);
            $key = $date->toDateString();
            $labels[] = $date->format('M d');
            $joinsData[] = $joins->get($key, 0);
            $leavesData[] = $leaves->get($key, 0) * -1;
        }

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::discord.dashboard.member_growth.joins'),
                    'data' => $joinsData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                ],
                [
                    'label' => __('panel-admin::discord.dashboard.member_growth.leaves'),
                    'data' => $leavesData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
        ];
    }
}
