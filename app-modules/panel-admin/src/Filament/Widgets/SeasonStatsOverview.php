<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Season\Models\Season;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class SeasonStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $currentSeason = Season::query()->whereNull('ended_at')->first();

        return [
            Stat::make('Current Season', $currentSeason?->name ?? 'None')
                ->icon(Heroicon::Flag),
            Stat::make('Total XP Distributed', Number::abbreviate(Character::query()->sum('experience')))
                ->icon(Heroicon::ArrowTrendingUp),
            Stat::make('Badges Claimed', DB::table('characters_badges')->count())
                ->icon(Heroicon::Star),
        ];
    }
}
