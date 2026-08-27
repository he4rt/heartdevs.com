<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use He4rt\IntegrationDiscord\Models\DiscordGuild;

class DiscordGuildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::discord.guilds.sections.overview'))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('icon')
                            ->label(__('panel-admin::discord.guilds.fields.icon'))
                            ->circular()
                            ->state(fn (DiscordGuild $record): ?string => $record->icon
                                ? sprintf('https://cdn.discordapp.com/icons/%s/%s.png?size=128', $record->discord_guild_id, $record->icon)
                                : null),

                        TextEntry::make('name')
                            ->label(__('panel-admin::discord.guilds.fields.name'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('discord_guild_id')
                            ->label(__('panel-admin::discord.guilds.fields.discord_guild_id'))
                            ->copyable(),

                        TextEntry::make('description')
                            ->label(__('panel-admin::discord.guilds.fields.description'))
                            ->columnSpanFull()
                            ->placeholder('—'),

                        TextEntry::make('member_count')
                            ->label(__('panel-admin::discord.guilds.fields.member_count'))
                            ->numeric(),

                        TextEntry::make('premium_tier')
                            ->label(__('panel-admin::discord.guilds.fields.premium_tier'))
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => 'Tier '.$state)
                            ->color(fn (int $state): string => match (true) {
                                $state >= 3 => 'success',
                                $state >= 1 => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('synced_at')
                            ->label(__('panel-admin::discord.guilds.fields.synced_at'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),
                    ]),

                Section::make(__('panel-admin::discord.guilds.sections.features'))
                    ->collapsed()
                    ->schema([
                        TextEntry::make('features')
                            ->label(__('panel-admin::discord.guilds.fields.features'))
                            ->badge()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
