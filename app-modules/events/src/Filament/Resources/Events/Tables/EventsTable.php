<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('active')
                    ->searchable(),
                TextColumn::make('max_attendees'),
                TextColumn::make('event_at'),

                TextColumn::make('start_at')
                    ->label('Event Hour')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i'))
                    ->description(fn ($record) => $record->end_at->format('d/m/Y H:i'))
                    ->sortable(),
            ])
            ->filters([
                //
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
