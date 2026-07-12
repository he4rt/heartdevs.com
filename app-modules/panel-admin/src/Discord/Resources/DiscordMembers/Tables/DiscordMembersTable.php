<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordMember;

class DiscordMembersTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('joined_at', 'desc')
            // Cursor pagination (padrão global) quebra ao ordenar pelo agregado
            // roles_count na página seguinte; length-aware suporta.
            ->paginationMode(PaginationMode::Default)
            ->paginated([25, 50, 100])
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('panel-admin::discord.members.fields.avatar'))
                    ->circular()
                    ->state(fn (DiscordMember $record): ?string => $record->avatar
                        ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=64', $record->discord_user_id, $record->avatar)
                        : null)
                    ->defaultImageUrl(fn (DiscordMember $record): string => sprintf('https://cdn.discordapp.com/embed/avatars/%d.png', ((int) $record->discord_user_id >> 22) % 6)),

                TextColumn::make('username')
                    ->label(__('panel-admin::discord.members.fields.username'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn (DiscordMember $record): ?string => $record->nickname ?? $record->global_name),

                TextColumn::make('joined_at')
                    ->label(__('panel-admin::discord.members.fields.joined_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable(),

                TextColumn::make('roles_count')
                    ->label(__('panel-admin::discord.members.fields.roles_count'))
                    ->counts('roles')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_bot')
                    ->label(__('panel-admin::discord.members.fields.is_bot'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('premium_since')
                    ->label(__('panel-admin::discord.members.fields.premium_since'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('left_at')
                    ->label(__('panel-admin::discord.members.fields.left_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discord_user_id')
                    ->label(__('panel-admin::discord.members.fields.discord_user_id'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('left_at')
                    ->label(__('panel-admin::discord.members.filters.left.label'))
                    ->nullable()
                    ->default(state: false)
                    ->trueLabel(__('panel-admin::discord.members.filters.left.true'))
                    ->falseLabel(__('panel-admin::discord.members.filters.left.false'))
                    ->placeholder(__('panel-admin::discord.members.filters.left.placeholder')),

                TernaryFilter::make('is_bot')
                    ->label(__('panel-admin::discord.members.filters.is_bot')),

                TernaryFilter::make('is_pending')
                    ->label(__('panel-admin::discord.members.filters.is_pending')),

                SelectFilter::make('roles')
                    ->label(__('panel-admin::discord.members.filters.roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
