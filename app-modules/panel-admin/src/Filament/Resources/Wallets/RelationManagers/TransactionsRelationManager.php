<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Wallets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('amount')
                    ->numeric(0)
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('balance_after')
                    ->numeric(0),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
