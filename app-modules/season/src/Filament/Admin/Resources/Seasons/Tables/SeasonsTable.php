<?php

declare(strict_types=1);

namespace He4rt\Season\Filament\Admin\Resources\Seasons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeasonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('started_at')
                    ->label('Season')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i'))
                    ->description(fn ($record) => $record->ended_at?->format('d/m/Y H:i'))
                    ->sortable(),
                TextColumn::make('messages_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('participants_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('meeting_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('badges_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
