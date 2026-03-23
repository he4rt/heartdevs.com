<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\Activity\Models\Message;
use Illuminate\Support\Carbon;

class ActivityChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => Carbon::today()->subDays($i));

        $counts = Message::query()
            ->where('sent_at', '>=', Carbon::today()->subDays(30))
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(sent_at)')
            ->pluck('count', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Messages',
                    'data' => $days->map(fn (Carbon $day) => $counts->get($day->toDateString(), 0))->toArray(),
                ],
            ],
            'labels' => $days->map(fn (Carbon $day) => $day->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
