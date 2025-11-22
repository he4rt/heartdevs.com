<?php

declare(strict_types=1);

namespace He4rt\Season\Filament\Shared\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Season\Models\Season;
use Illuminate\Contracts\Database\Query\Builder;

class SeasonStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $season = Season::query()
            ->where('started_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('ended_at')
                ->orWhere('ended_at', '>', now())
            )
            ->latest('started_at')
            ->first();

        if (! $season) {
            return [
                Stat::make('Season ativa', 'Nenhuma')
                    ->description('Não há season em andamento')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('XP total da season', $season->messages_count)
                ->description('Experiência acumulada')
                ->descriptionIcon('heroicon-o-fire')
                ->color('primary'),

            Stat::make('Mensagens processadas', $season->messages_count)
                ->description('Total de mensagens registradas')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('success'),

            Stat::make('Participantes', $season->participants_count)
                ->description('Usuários ativos na season')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),
        ];
    }
}
