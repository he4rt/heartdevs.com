<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Wallets\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Economy\Enums\Currency;
use He4rt\Economy\Enums\TransactionType;

class WalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner_type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => class_basename($state)),
                TextColumn::make('currency')
                    ->badge(),
                TextColumn::make('balance')
                    ->numeric(0)
                    ->sortable(),
                TextColumn::make('transactions_count')
                    ->counts('transactions')
                    ->label('Transactions'),
            ])
            ->filters([
                SelectFilter::make('currency')
                    ->options(Currency::class),
            ])
            ->recordActions([
                Action::make('credit')
                    ->icon(Heroicon::Plus)
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->required()
                            ->integer()
                            ->minValue(1),
                        TextInput::make('description')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->increment('balance', (int) $data['amount']);
                        $record->transactions()->create([
                            'type' => TransactionType::Reward,
                            'amount' => (int) $data['amount'],
                            'balance_after' => $record->fresh()->balance,
                            'description' => $data['description'] ?? 'Admin credit',
                        ]);
                    })
                    ->successNotificationTitle('Wallet credited'),
            ]);
    }
}
