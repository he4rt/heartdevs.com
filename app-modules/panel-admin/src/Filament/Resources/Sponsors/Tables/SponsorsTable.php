<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Sponsors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SponsorsTable
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
                TextColumn::make('homepage_url')
                    ->limit(30)
                    ->url(fn ($record) => $record->homepage_url, true),
                TextColumn::make('events_count')
                    ->counts('events')
                    ->label('Events'),
            ])
            ->filters([])
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
