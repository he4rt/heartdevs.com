<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscordMembersTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guild.name')
                    ->label('Discord Guild Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discord_user_id')
                    ->label('Discord User Id'),

                TextColumn::make('externalIdentity.email')
                    ->label('External Identity Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username'),

                TextColumn::make('global_name')
                    ->label('Global Name'),

                TextColumn::make('avatar')
                    ->label('Avatar'),

                TextColumn::make('nickname')
                    ->label('Nickname'),

                TextColumn::make('is_bot')
                    ->label('Is Bot'),

                TextColumn::make('is_pending')
                    ->label('Is Pending'),

                TextColumn::make('joined_at')
                    ->label('Joined At'),

                TextColumn::make('premium_since')
                    ->label('Premium Since'),

                TextColumn::make('communication_disabled_until')
                    ->label('Communication Disabled Until'),

                TextColumn::make('left_at')
                    ->label('Left At'),
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
