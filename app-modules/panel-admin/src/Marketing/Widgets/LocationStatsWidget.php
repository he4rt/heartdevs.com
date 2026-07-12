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

        $webTrend = $stats->webTrend();
        $discordTrend = $stats->discordTrend();

        return [
            Stat::make(__('panel-admin::location.stats.web_active'), number_format($stats->webActive))
                ->description(abs($webTrend).__('panel-admin::location.stats.vs_previous'))
                ->descriptionIcon($webTrend >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($webTrend >= 0 ? Color::Purple : Color::Red),

            Stat::make(__('panel-admin::location.stats.discord_active'), number_format($stats->discordActive))
                ->description(abs($discordTrend).__('panel-admin::location.stats.vs_previous'))
                ->descriptionIcon($discordTrend >= 0 ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->color($discordTrend >= 0 ? Color::Green : Color::Red),

            Stat::make(__('panel-admin::location.stats.located'), number_format($stats->locatedMembers))
                ->description(__('panel-admin::location.stats.coverage', ['percent' => $stats->coverage()]))
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color(Color::Amber),

            Stat::make(__('panel-admin::location.stats.states'), sprintf('%d/%d', $stats->statesReached, $stats->statesTotal))
                ->description(__('panel-admin::location.stats.states_hint'))
                ->descriptionIcon(Heroicon::OutlinedGlobeAmericas)
                ->color(Color::Blue),
        ];
    }
}
