<?php

declare(strict_types=1);

namespace He4rt\Tenant\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Tenant\Models\Tenant;

class TenantsStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalActive = Tenant::query()->where('active', true)->count();

        $createdThisMonth = Tenant::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $tenantMostUsers = Tenant::query()
            ->withCount('members')
            ->orderByDesc('members_count')
            ->first();

        $tenantMostMessages = Tenant::query()->withCount('messages')
            ->orderByDesc('messages_count')
            ->first();

        return [
            Stat::make('Tenants ativos', $totalActive)
                ->description('Ativos no sistema')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Criados neste mês', $createdThisMonth)
                ->description('Novos tenants')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('primary'),

            Stat::make('Mais usuários', $tenantMostUsers?->name ?? 'Nenhum')
                ->description($tenantMostUsers ? $tenantMostUsers->members_count.' usuários' : '')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('warning'),

            Stat::make('Mais mensagens', $tenantMostMessages?->name ?? 'Nenhum')
                ->description($tenantMostMessages ? $tenantMostMessages->messages_count.' msgs' : '')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('info'),
        ];
    }
}
