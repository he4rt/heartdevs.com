<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProvidersRelationManager extends RelationManager
{
    protected static string $relationship = 'providers';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->badge(),
                TextColumn::make('external_account_id')
                    ->copyable()
                    ->label('External ID'),
                TextColumn::make('credentials_type')
                    ->badge(),
                TextColumn::make('connected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('disconnected_at')
                    ->dateTime()
                    ->placeholder('Connected'),
            ])
            ->actions([
                Action::make('disconnect')
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->visible(fn ($record) => $record->disconnected_at === null)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['disconnected_at' => now()]))
                    ->successNotificationTitle('Identity disconnected'),
            ]);
    }
}
