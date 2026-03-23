<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->icon(Heroicon::Users),
            Stat::make('Active Tenants', Tenant::where('active', true)->count())
                ->icon(Heroicon::BuildingOffice),
            Stat::make('Total Characters', Character::count())
                ->icon(Heroicon::UserCircle),
            Stat::make('Active Events', EventModel::where('active', true)->count())
                ->icon(Heroicon::Ticket),
        ];
    }
}
