<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\EventModels\Pages;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use He4rt\Events\Filament\App\EventModels\EventModelResource;

class ViewEventModel extends ViewRecord
{
    protected static string $resource = EventModelResource::class;

    protected static ?string $title = 'Event Details';

    protected string $view = 'events::app.view-event';

    public function eventInfoList(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->components([
                Section::make('Detalhes do Evento')
                    ->description('Informações básicas e descrição do evento.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Título')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),

                                TextEntry::make('event_type')
                                    ->label('Tipo de Evento')
                                    ->badge(),
                            ]),

                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                IconEntry::make('active')
                                    ->label('Status')
                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->alignment(Alignment::Start),
                            ]),
                        TextEntry::make('description')
                            ->label('Descrição Detalhada')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),

                Section::make('Agenda e Local')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('event_at')
                            ->label('Data e Hora do Evento')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-o-calendar-days'),

                        TextEntry::make('start_at')
                            ->label('Início')
                            ->dateTime('H:i')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('end_at')
                            ->label('Fim')
                            ->dateTime('H:i')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('location')
                            ->label('Localização')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),
                    ]),
                Section::make('Participação')
                    ->icon('heroicon-o-users')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('max_attendees')
                            ->label('Capacidade Máxima')
                            ->numeric()
                            ->icon('heroicon-o-user-group')
                            ->badge(),

                        TextEntry::make('attendees_count')
                            ->label('Participantes Confirmados')
                            ->numeric()
                            ->color('success')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('waitlist_count')
                            ->label('Lista de Espera')
                            ->numeric()
                            ->color('warning')
                            ->icon('heroicon-o-queue-list'),
                    ]),
            ]);
    }
}
