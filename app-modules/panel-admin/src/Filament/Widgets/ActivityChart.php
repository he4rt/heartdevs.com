<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use He4rt\Activity\Message\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

class ActivityChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i) => Date::today()->subDays($i));

        $counts = Message::query()
            ->where('sent_at', '>=', Date::today()->subDays(30))
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
            'labels' => $days->map(fn (Carbon $day) => $day->format('M d'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
