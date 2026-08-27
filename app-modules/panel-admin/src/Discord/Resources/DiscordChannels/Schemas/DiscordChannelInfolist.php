<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class DiscordChannelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::discord.channels.sections.channel'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('panel-admin::discord.channels.fields.name'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('type')
                            ->label(__('panel-admin::discord.channels.fields.type'))
                            ->badge(),

                        TextEntry::make('topic')
                            ->label(__('panel-admin::discord.channels.fields.topic'))
                            ->columnSpanFull()
                            ->placeholder('—'),

                        TextEntry::make('parent.name')
                            ->label(__('panel-admin::discord.channels.fields.category'))
                            ->placeholder('—'),

                        TextEntry::make('guild.name')
                            ->label(__('panel-admin::discord.channels.fields.guild'))
                            ->url(fn (DiscordChannel $record): string => DiscordGuildResource::getUrl('view', ['record' => $record->guild]))
                            ->color('primary'),

                        TextEntry::make('discord_channel_id')
                            ->label(__('panel-admin::discord.channels.fields.discord_channel_id'))
                            ->copyable(),
                    ]),

                Section::make(__('panel-admin::discord.channels.sections.settings'))
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('position')
                            ->label(__('panel-admin::discord.channels.fields.position'))
                            ->numeric(),

                        IconEntry::make('nsfw')
                            ->label(__('panel-admin::discord.channels.fields.nsfw'))
                            ->boolean(),

                        TextEntry::make('bitrate')
                            ->label(__('panel-admin::discord.channels.fields.bitrate'))
                            ->formatStateUsing(fn (?int $state): ?string => $state ? ($state / 1_000).' kbps' : null)
                            ->placeholder('—'),

                        TextEntry::make('user_limit')
                            ->label(__('panel-admin::discord.channels.fields.user_limit'))
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label(__('panel-admin::discord.channels.fields.created_at'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone')),

                        TextEntry::make('updated_at')
                            ->label(__('panel-admin::discord.channels.fields.updated_at'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone')),
                    ]),
            ]);
    }
}
