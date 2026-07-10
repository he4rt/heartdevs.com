<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Widgets;

use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\PanelAdmin\Marketing\Pages\Location\Queries\CommunityActivityStats;

class LocationStatsWidget extends StatsOverviewWidget
{
    private const int RANGE_DAYS = 30;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Stat[]
     */
    protected function getStats(): array
    {
        $stats = new CommunityActivityStats(self::RANGE_DAYS)->get();

        $webDiff = $this->pctDiff($stats['web_active'], $stats['web_active_prev']);
        $discordDiff = $this->pctDiff($stats['discord_active'], $stats['discord_active_prev']);
        $coverage = $stats['total_members'] > 0
            ? (int) round($stats['located_members'] / $stats['total_members'] * 100)
            : 0;

        return [
            Stat::make(__('panel-admin::location.stats.web_active'), number_format($stats['web_active']))
                ->description(abs($webDiff).__('panel-admin::location.stats.vs_previous'))
                ->descriptionIcon($webDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($webDiff >= 0 ? Color::Purple : Color::Red),

            Stat::make(__('panel-admin::location.stats.discord_active'), number_format($stats['discord_active']))
                ->description(abs($discordDiff).__('panel-admin::location.stats.vs_previous'))
                ->descriptionIcon($discordDiff >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($discordDiff >= 0 ? Color::Green : Color::Red),

            Stat::make(__('panel-admin::location.stats.located'), number_format($stats['located_members']))
                ->description(__('panel-admin::location.stats.coverage', ['percent' => $coverage]))
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color(Color::Amber),

            Stat::make(__('panel-admin::location.stats.states'), $stats['states_reached'].'/'.$stats['states_total'])
                ->description(__('panel-admin::location.stats.states_hint'))
                ->descriptionIcon(Heroicon::OutlinedGlobeAmericas)
                ->color(Color::Blue),
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
