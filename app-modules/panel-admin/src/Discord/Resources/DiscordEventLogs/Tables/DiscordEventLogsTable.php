<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscordEventLogsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event_type')
                    ->label('Event Type'),

                TextColumn::make('guild_id')
                    ->label('Guild Id'),

                TextColumn::make('user_id')
                    ->label('User Id'),

                TextColumn::make('channel_id')
                    ->label('Channel Id'),

                TextColumn::make('payload')
                    ->label('Payload'),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
