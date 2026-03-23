<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Transactions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Economy\Enums\TransactionType;

class TransactionsTable
{
    public static function configure(Table $table): Table
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
                    ->limit(50),
                TextColumn::make('reference_type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(TransactionType::class),
            ]);
    }
}
