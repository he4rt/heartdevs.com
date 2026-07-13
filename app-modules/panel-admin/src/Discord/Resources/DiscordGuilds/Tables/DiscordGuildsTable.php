<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Tables;

use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordGuild;

class DiscordGuildsTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('icon')
                    ->label(__('panel-admin::discord.guilds.fields.icon'))
                    ->circular()
                    ->state(fn (DiscordGuild $record): ?string => $record->icon
                        ? sprintf('https://cdn.discordapp.com/icons/%s/%s.png?size=64', $record->discord_guild_id, $record->icon)
                        : null),

                TextColumn::make('name')
                    ->label(__('panel-admin::discord.guilds.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (DiscordGuild $record): ?string => $record->description),

                TextColumn::make('member_count')
                    ->label(__('panel-admin::discord.guilds.fields.member_count'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('premium_tier')
                    ->label(__('panel-admin::discord.guilds.fields.premium_tier'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => 'Tier '.$state)
                    ->color(fn (int $state): string => match (true) {
                        $state >= 3 => 'success',
                        $state >= 1 => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('channels_count')
                    ->label(__('panel-admin::discord.guilds.fields.channels_count'))
                    ->counts('channels')
                    ->numeric(),

                TextColumn::make('roles_count')
                    ->label(__('panel-admin::discord.guilds.fields.roles_count'))
                    ->counts('roles')
                    ->numeric(),

                TextColumn::make('synced_at')
                    ->label(__('panel-admin::discord.guilds.fields.synced_at'))
                    ->dateTime('d/m/Y H:i')
                    ->timezone(config('app.display_timezone'))
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('discord_guild_id')
                    ->label(__('panel-admin::discord.guilds.fields.discord_guild_id'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
