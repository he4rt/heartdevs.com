<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Shared\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\User\Models\User;

use function Illuminate\Support\minutes;

class UsersStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $query = User::query();

        $totalDonators = cache()->remember('total_users_donators', minutes(5), fn () => $query
            ->where('is_donator', true)
            ->count());

        $totalUsersToday = cache()->remember('total_users_today', minutes(5), fn () => $query
            ->whereDate('created_at', today())
            ->count());

        $totalUsersMonth = cache()->remember('total_users_month', minutes(5), fn () => $query
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count());

        return [
            Stat::make('Usuários criados hoje', $totalUsersToday)
                ->description('Novos usuários nas últimas 24h')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('success'),

            Stat::make('Usuários criados este mês', $totalUsersMonth)
                ->description('Total no mês atual')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),

            Stat::make('Usuários doadores', $totalDonators)
                ->description('Usuários com doação ativa')
                ->descriptionIcon('heroicon-o-heart')
                ->color('warning'),
        ];
    }
}
