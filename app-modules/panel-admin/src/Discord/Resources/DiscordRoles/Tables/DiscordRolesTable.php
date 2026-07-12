<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscordRolesTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('guild.name')
                    ->label('Discord Guild Id')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discord_role_id')
                    ->label('Discord Role Id'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Color'),

                TextColumn::make('position')
                    ->label('Position'),

                TextColumn::make('permissions')
                    ->label('Permissions'),

                TextColumn::make('is_hoisted')
                    ->label('Is Hoisted'),

                TextColumn::make('is_mentionable')
                    ->label('Is Mentionable'),

                TextColumn::make('is_managed')
                    ->label('Is Managed'),

                TextColumn::make('icon')
                    ->label('Icon'),
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
