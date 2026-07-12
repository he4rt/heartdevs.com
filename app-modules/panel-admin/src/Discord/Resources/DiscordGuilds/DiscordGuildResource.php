<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\ListDiscordGuilds;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\ViewDiscordGuild;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\ChannelsRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\MembersRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\RelationManagers\RolesRelationManager;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas\DiscordGuildInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Tables\DiscordGuildsTable;

class DiscordGuildResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $model = DiscordGuild::class;

    protected static ?string $slug = 'discord-guilds';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.guilds');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_server');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::discord.guilds.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::discord.guilds.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordGuildInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordGuildsTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDiscordGuilds::route('/'),
            'view' => ViewDiscordGuild::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            ChannelsRelationManager::class,
            RolesRelationManager::class,
            MembersRelationManager::class,
        ];
    }
}
