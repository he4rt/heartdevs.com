<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DiscordStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $timeline = [
            ['day' => '05/05', 'msgs' => 214, 'users' => 19, 'voiceHours' => 4.2],
            ['day' => '06/05', 'msgs' => 360, 'users' => 36, 'voiceHours' => 5.8],
            ['day' => '07/05', 'msgs' => 453, 'users' => 33, 'voiceHours' => 5.1],
            ['day' => '08/05', 'msgs' => 482, 'users' => 35, 'voiceHours' => 6.4],
            ['day' => '09/05', 'msgs' => 190, 'users' => 20, 'voiceHours' => 3.6],
            ['day' => '10/05', 'msgs' => 71, 'users' => 15, 'voiceHours' => 2.1],
            ['day' => '11/05', 'msgs' => 732, 'users' => 27, 'voiceHours' => 6.8],
            ['day' => '12/05', 'msgs' => 2729, 'users' => 83, 'voiceHours' => 9.5],
            ['day' => '13/05', 'msgs' => 519, 'users' => 38, 'voiceHours' => 6.2],
            ['day' => '14/05', 'msgs' => 1135, 'users' => 38, 'voiceHours' => 8.3],
            ['day' => '15/05', 'msgs' => 524, 'users' => 34, 'voiceHours' => 7.1],
            ['day' => '16/05', 'msgs' => 47, 'users' => 18, 'voiceHours' => 2.8],
            ['day' => '17/05', 'msgs' => 34, 'users' => 18, 'voiceHours' => 1.9],
            ['day' => '18/05', 'msgs' => 257, 'users' => 27, 'voiceHours' => 5.2],
        ];

        $week1 = array_slice($timeline, 0, 7);
        $week2 = array_slice($timeline, 7, 7);

        $w1Msgs = array_sum(array_column($week1, 'msgs'));
        $w2Msgs = array_sum(array_column($week2, 'msgs'));
        $w1Voice = array_sum(array_column($week1, 'voiceHours'));
        $w2Voice = array_sum(array_column($week2, 'voiceHours'));
        $w1Users = max(...array_column($week1, 'users'));
        $w2Users = max(...array_column($week2, 'users'));
        $w1Avg = (int) round($w1Msgs / max(count($week1), 1));
        $w2Avg = (int) round($w2Msgs / max(count($week2), 1));

        $msgsDiff = $this->pctDiff($w2Msgs, $w1Msgs);
        $voiceDiff = $this->pctDiff($w2Voice, $w1Voice);
        $usersDiff = $this->pctDiff($w2Users, $w1Users);
        $avgDiff = $this->pctDiff($w2Avg, $w1Avg);

        return [
            Stat::make('Total Mensagens', number_format($w1Msgs + $w2Msgs))
                ->description(abs($msgsDiff).'% vs semana anterior')
                ->descriptionIcon($msgsDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart(array_column($timeline, 'msgs'))
                ->color($msgsDiff >= 0 ? Color::Purple : Color::Red),

            Stat::make('Horas em Voice', round($w1Voice + $w2Voice).'h')
                ->description(abs($voiceDiff).'% vs semana anterior')
                ->descriptionIcon($voiceDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart(array_map(fn (array $d): float => $d['voiceHours'], $timeline))
                ->color($voiceDiff >= 0 ? Color::Green : Color::Red),

            Stat::make('Usuários Únicos', number_format(max($w1Users, $w2Users)))
                ->description(abs($usersDiff).'% vs semana anterior')
                ->descriptionIcon($usersDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart(array_column($timeline, 'users'))
                ->color($usersDiff >= 0 ? Color::Amber : Color::Red),

            Stat::make('Média / Dia', number_format((int) round(($w1Msgs + $w2Msgs) / max(count($timeline), 1))))
                ->description(abs($avgDiff).'% vs semana anterior')
                ->descriptionIcon($avgDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->chart(array_column($timeline, 'msgs'))
                ->color($avgDiff >= 0 ? Color::Blue : Color::Red),
        ];
    }

    private function pctDiff(int|float $current, int|float $previous): float
    {
        if ($previous === 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
