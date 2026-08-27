<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\PeriodStats;

class DiscordStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $stats = new PeriodStats(14)->statCards();

        $msgsDiff = $this->pctDiff($stats['total_msgs'], $stats['prev_msgs']);
        $voiceDiff = $this->pctDiff($stats['total_voice'], $stats['prev_voice']);
        $usersDiff = $this->pctDiff($stats['total_users'], $stats['prev_users']);
        $avgDiff = $this->pctDiff($stats['avg_per_day'], $stats['prev_avg']);

        return [
            Stat::make('Total Mensagens', number_format($stats['total_msgs']))
                ->description(abs($msgsDiff).'% vs período anterior')
                ->descriptionIcon($msgsDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart($stats['sparkline_msgs'])
                ->color($msgsDiff >= 0 ? Color::Purple : Color::Red),

            Stat::make('Horas em Voice', round($stats['total_voice']).'h')
                ->description(abs($voiceDiff).'% vs período anterior')
                ->descriptionIcon($voiceDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart(array_map(fn (float $v): int => (int) round($v), $stats['sparkline_voice']))
                ->color($voiceDiff >= 0 ? Color::Green : Color::Red),

            Stat::make('Usuários Únicos', number_format($stats['total_users']))
                ->description(abs($usersDiff).'% vs período anterior')
                ->descriptionIcon($usersDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart($stats['sparkline_users'])
                ->color($usersDiff >= 0 ? Color::Amber : Color::Red),

            Stat::make('Média / Dia', number_format($stats['avg_per_day']))
                ->description(abs($avgDiff).'% vs período anterior')
                ->descriptionIcon($avgDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart($stats['sparkline_msgs'])
                ->color($avgDiff >= 0 ? Color::Blue : Color::Red),
        ];
    }

    private function pctDiff(int|float $current, int|float $previous): float
    {
        if ($previous === 0 || $previous === 0.0) {
            return 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
