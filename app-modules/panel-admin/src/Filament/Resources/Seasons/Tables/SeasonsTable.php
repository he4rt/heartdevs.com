<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeasonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Active'),

                TextColumn::make('participants_count')
                    ->numeric(0),

                TextColumn::make('messages_count')
                    ->numeric(0),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
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
