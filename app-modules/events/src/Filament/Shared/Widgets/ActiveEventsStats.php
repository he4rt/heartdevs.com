<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Shared\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use He4rt\Events\Models\EventModel;

class ActiveEventsStats extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        $query = EventModel::query()->where('active', true);

        if (! $user->isAdmin()) {
            $query->where('tenant_id', Filament::getTenant()->getKey());
        }

        $event = $query->first();

        if (! $event) {
            return [
                Stat::make('Evento ativo', 'Nenhum')
                    ->description('Sem evento em andamento')
                    ->color('gray'),
            ];
        }

        return [
            Stat::make('Evento ativo', $event->title)
                ->description(
                    $event->start_at->isPast()
                        ? 'Evento já começou'
                        : 'Começa em '.$event->start_at->diffForHumans()
                )
                ->descriptionIcon('heroicon-o-calendar')
                ->color($event->start_at->isPast() ? 'warning' : 'primary'),

            Stat::make('Inscritos', $event->attendees_count)
                ->description('Capacidade: '.$event->max_attendees)
                ->descriptionIcon('heroicon-o-user-group')
                ->color($event->attendees_count >= $event->max_attendees ? 'danger' : 'success'),

            Stat::make('Lista de espera', $event->waitlist_count)
                ->description('Pessoas aguardando vaga')
                ->descriptionIcon('heroicon-o-clock')
                ->color($event->waitlist_count > 0 ? 'warning' : 'gray'),

            Stat::make('Talks cadastradas', $event->talks()->count())
                ->description('Sessões confirmadas')
                ->descriptionIcon('heroicon-o-presentation-chart-bar')
                ->color('primary'),

            Stat::make('Sponsors', $event->attendees()->count())
                ->description('Patrocinadores do evento')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }
}
