<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Tables;

use Carbon\CarbonInterface;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;

class UpcomingEventsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label(__('panel-admin::agenda.form.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label(__('panel-admin::agenda.form.category'))
                    ->badge()
                    ->formatStateUsing(fn (UpcomingEventCategory $state): string => $state->getLabel())
                    ->color(fn (UpcomingEventCategory $state): string => match ($state) {
                        UpcomingEventCategory::ReuniaoSemanal => 'primary',
                        UpcomingEventCategory::Aula => 'info',
                        UpcomingEventCategory::AulaIngles => 'success',
                        UpcomingEventCategory::Onboarding => 'warning',
                        UpcomingEventCategory::Networking => 'danger',
                    }),

                TextColumn::make('week_day')
                    ->label(__('panel-admin::agenda.form.week_day'))
                    ->formatStateUsing(fn (?int $state): string => $state === null
                        ? '-'
                        : __('panel-admin::agenda.weekdays.'.$state)),

                TextColumn::make('time')
                    ->label(__('panel-admin::agenda.form.time'))
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

                TextColumn::make('next_occurrence')
                    ->label(__('panel-admin::agenda.table.next_occurrence'))
                    ->state(fn (UpcomingEvent $record): ?CarbonInterface => $record->nextOccurrence())
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('panel-admin::agenda.form.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
