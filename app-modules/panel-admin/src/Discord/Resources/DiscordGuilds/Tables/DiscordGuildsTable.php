<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscordGuildsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('discord_guild_id')
                    ->label('Discord Guild Id'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('icon')
                    ->label('Icon'),

                TextColumn::make('description')
                    ->label('Description'),

                TextColumn::make('member_count')
                    ->label('Member Count'),

                TextColumn::make('premium_tier')
                    ->label('Premium Tier'),

                TextColumn::make('features')
                    ->label('Features'),

                TextColumn::make('synced_at')
                    ->label('Synced At'),
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
