<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class DiscordRoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('panel-admin::discord.roles.sections.role'))
                    ->columns(2)
                    ->schema([
                        ColorEntry::make('color')
                            ->label(__('panel-admin::discord.roles.fields.color'))
                            ->state(fn (DiscordRole $record): ?string => $record->color > 0
                                ? sprintf('#%06X', $record->color)
                                : null)
                            ->placeholder('—'),

                        TextEntry::make('name')
                            ->label(__('panel-admin::discord.roles.fields.name'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('position')
                            ->label(__('panel-admin::discord.roles.fields.position'))
                            ->numeric(),

                        TextEntry::make('members_count')
                            ->label(__('panel-admin::discord.roles.fields.members_count'))
                            ->state(fn (DiscordRole $record): int => $record->members()->count()),

                        TextEntry::make('permissions')
                            ->label(__('panel-admin::discord.roles.fields.permissions'))
                            ->copyable()
                            ->helperText(__('panel-admin::discord.roles.fields.permissions_helper')),

                        TextEntry::make('discord_role_id')
                            ->label(__('panel-admin::discord.roles.fields.discord_role_id'))
                            ->copyable(),

                        IconEntry::make('is_hoisted')
                            ->label(__('panel-admin::discord.roles.fields.is_hoisted'))
                            ->boolean(),

                        IconEntry::make('is_mentionable')
                            ->label(__('panel-admin::discord.roles.fields.is_mentionable'))
                            ->boolean(),

                        IconEntry::make('is_managed')
                            ->label(__('panel-admin::discord.roles.fields.is_managed'))
                            ->boolean(),

                        TextEntry::make('guild.name')
                            ->label(__('panel-admin::discord.roles.fields.guild'))
                            ->url(fn (DiscordRole $record): string => DiscordGuildResource::getUrl('view', ['record' => $record->guild]))
                            ->color('primary'),
                    ]),
            ]);
    }
}
