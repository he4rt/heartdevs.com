<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->limit(20)
                    ->searchable(),
                TextColumn::make('slug')
                    ->visible(false)
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->badge()
                    ->searchable(),
                IconColumn::make('active')
                    ->sortable()
                    ->boolean(),

                TextColumn::make('attendees_count')
                    ->label('Inscritos')
                    ->sortable()
                    ->formatStateUsing(fn (string $state, $record) => sprintf('%s/%s', $state, $record->max_attendees)),

                TextColumn::make('talks_count')
                    ->label('Submissions')
                    ->sortable()
                    ->counts('talks'),

                TextColumn::make('event_at')
                    ->sortable()
                    ->date(),
                IconColumn::make('active')
                    ->searchable(),
                TextColumn::make('max_attendees'),
                TextColumn::make('event_at')
                    ->searchable(),
                TextColumn::make('max_attendees'),
                TextColumn::make('event_at'),

                TextColumn::make('start_at')
                    ->label('EventModel Hour')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i'))
                    ->description(fn ($record) => $record->end_at->format('d/m/Y H:i'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
