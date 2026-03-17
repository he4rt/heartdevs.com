<?php

declare(strict_types=1);

namespace He4rt\Gamification\Season\Filament\Shared\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Gamification\Season\Models\Season;
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
            Stat::make('Season ativa', $season->name)
                ->description(
                    $season->ended_at
                        ? 'Finalizada em '.$season->ended_at->diffForHumans()
                        : 'Iniciada em '.$season->started_at->diffForHumans()
                )
                ->descriptionIcon('heroicon-o-flag')
                ->color($season->ended_at ? 'gray' : 'primary'),

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
