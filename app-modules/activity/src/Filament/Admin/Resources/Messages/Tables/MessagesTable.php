<?php

declare(strict_types=1);

namespace He4rt\Activity\Filament\Admin\Resources\Messages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider.provider')
                    ->badge()
                    ->label('Provider')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('channel_id')
                    ->label('Channel')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('content')
                    ->label('Content')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->sortable(),

                TextColumn::make('obtained_experience')
                    ->label('Obteined XP')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
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
