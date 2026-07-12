<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordGuild;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas\DiscordGuildForm;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas\DiscordGuildInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Tables\DiscordGuildsTable;

class DiscordGuildResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;
    protected static ?string $model = DiscordGuild::class;

    protected static ?string $slug = 'discord-guilds';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscordGuildForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordGuildInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordGuildsTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\ListDiscordGuilds::route('/'),
            'create' => \He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\CreateDiscordGuild::route('/create'),
            'edit' => \He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages\EditDiscordGuild::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
