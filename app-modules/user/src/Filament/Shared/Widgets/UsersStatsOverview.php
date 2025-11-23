<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Shared\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\User\Models\User;
use Illuminate\Contracts\Database\Query\Builder;

class UsersStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        $query = User::query();

        if (! $user->isAdmin()) {
            $query->whereHas('tenants', fn (Builder $q) => $q->where('tenants.id', Filament::getTenant()->getKey())
            );
        }

        $totalDonators = (clone $query)->where('is_donator', true)->count();
        $totalUsersToday = (clone $query)->whereDate('created_at', today())->count();
        $totalUsersMonth = (clone $query)->whereMonth('created_at', now()->month)->count();

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
