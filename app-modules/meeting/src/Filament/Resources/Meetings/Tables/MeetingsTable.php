<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\Meetings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeetingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('admin.name')
                    ->label('Admin')
                    ->searchable()
                    ->badge(),
                TextColumn::make('meetingType.name')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Meeting Hour')
                    ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i'))
                    ->description(fn ($record) => $record->ends_at->format('d/m/Y H:i'))
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
