<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordRole;

class DiscordRolesTable
{
    public static function table(Table $table): Table
    {
        return $table
            // Cursor pagination (padrão global) quebra ao ordenar pelo agregado
            // members_count na página seguinte; length-aware suporta.
            ->paginationMode(PaginationMode::Default)
            ->columns([
                ColorColumn::make('color')
                    ->label(__('panel-admin::discord.roles.fields.color'))
                    ->state(fn (DiscordRole $record): ?string => $record->color > 0
                        ? sprintf('#%06X', $record->color)
                        : null),

                TextColumn::make('name')
                    ->label(__('panel-admin::discord.roles.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('position')
                    ->label(__('panel-admin::discord.roles.fields.position'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('members_count')
                    ->label(__('panel-admin::discord.roles.fields.members_count'))
                    ->counts('members')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_hoisted')
                    ->label(__('panel-admin::discord.roles.fields.is_hoisted'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_mentionable')
                    ->label(__('panel-admin::discord.roles.fields.is_mentionable'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_managed')
                    ->label(__('panel-admin::discord.roles.fields.is_managed'))
                    ->boolean(),

                TextColumn::make('discord_role_id')
                    ->label(__('panel-admin::discord.roles.fields.discord_role_id'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_managed')
                    ->label(__('panel-admin::discord.roles.filters.is_managed')),

                TernaryFilter::make('is_hoisted')
                    ->label(__('panel-admin::discord.roles.filters.is_hoisted')),
            ])
            ->defaultSort('position', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
