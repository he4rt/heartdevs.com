<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscordChannelsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guild.name')
                    ->label('Discord Guild Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discord_channel_id')
                    ->label('Discord Channel Id'),

                TextColumn::make('parent.name')
                    ->label('Parent Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type'),

                TextColumn::make('topic')
                    ->label('Topic'),

                TextColumn::make('position')
                    ->label('Position'),

                TextColumn::make('nsfw')
                    ->label('Nsfw'),

                TextColumn::make('bitrate')
                    ->label('Bitrate'),

                TextColumn::make('user_limit')
                    ->label('User Limit'),
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
