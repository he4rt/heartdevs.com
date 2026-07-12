<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class DiscordMemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::discord.members.sections.profile'))
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('avatar')
                            ->label(__('panel-admin::discord.members.fields.avatar'))
                            ->circular()
                            ->state(fn (DiscordMember $record): ?string => $record->avatar
                                ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=128', $record->discord_user_id, $record->avatar)
                                : null)
                            ->defaultImageUrl(fn (DiscordMember $record): string => sprintf('https://cdn.discordapp.com/embed/avatars/%d.png', ((int) $record->discord_user_id >> 22) % 6)),

                        TextEntry::make('username')
                            ->label(__('panel-admin::discord.members.fields.username'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('global_name')
                            ->label(__('panel-admin::discord.members.fields.global_name'))
                            ->placeholder('—'),

                        TextEntry::make('nickname')
                            ->label(__('panel-admin::discord.members.fields.nickname'))
                            ->placeholder('—'),

                        TextEntry::make('discord_user_id')
                            ->label(__('panel-admin::discord.members.fields.discord_user_id'))
                            ->copyable(),

                        TextEntry::make('guild.name')
                            ->label(__('panel-admin::discord.members.fields.guild'))
                            ->url(fn (DiscordMember $record): string => DiscordGuildResource::getUrl('view', ['record' => $record->guild]))
                            ->color('primary'),
                    ]),

                Section::make(__('panel-admin::discord.members.sections.status'))
                    ->columns(2)
                    ->schema([
                        IconEntry::make('is_bot')
                            ->label(__('panel-admin::discord.members.fields.is_bot'))
                            ->boolean(),

                        IconEntry::make('is_pending')
                            ->label(__('panel-admin::discord.members.fields.is_pending'))
                            ->boolean(),

                        TextEntry::make('joined_at')
                            ->label(__('panel-admin::discord.members.fields.joined_at'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone')),

                        TextEntry::make('premium_since')
                            ->label(__('panel-admin::discord.members.fields.premium_since'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),

                        TextEntry::make('communication_disabled_until')
                            ->label(__('panel-admin::discord.members.fields.communication_disabled_until'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),

                        TextEntry::make('left_at')
                            ->label(__('panel-admin::discord.members.fields.left_at'))
                            ->dateTime('d/m/Y H:i')
                            ->timezone(config('app.display_timezone'))
                            ->placeholder('—'),
                    ]),

                Section::make(__('panel-admin::discord.members.sections.roles'))
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label(__('panel-admin::discord.members.fields.roles'))
                            ->badge()
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
