<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\DiscordRoleResource;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position', 'desc')
            ->columns([
                ColorColumn::make('color')
                    ->label(__('panel-admin::discord.roles.fields.color'))
                    ->state(fn (DiscordRole $record): ?string => $record->color > 0
                        ? sprintf('#%06X', $record->color)
                        : null),

                TextColumn::make('name')
                    ->label(__('panel-admin::discord.roles.fields.name')),

                TextColumn::make('position')
                    ->label(__('panel-admin::discord.roles.fields.position'))
                    ->sortable(),

                IconColumn::make('is_managed')
                    ->label(__('panel-admin::discord.roles.fields.is_managed'))
                    ->boolean(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (DiscordRole $record): string => DiscordRoleResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
