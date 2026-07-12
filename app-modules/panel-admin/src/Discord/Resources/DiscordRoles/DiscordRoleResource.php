<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\ListDiscordRoles;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\ViewDiscordRole;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas\DiscordRoleInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Tables\DiscordRolesTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscordRoleResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $model = DiscordRole::class;

    protected static ?string $slug = 'discord-roles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.roles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_server');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::discord.roles.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::discord.roles.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordRoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordRolesTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDiscordRoles::route('/'),
            'view' => ViewDiscordRole::route('/{record}'),
        ];
    }

    /**
     * @return Builder<DiscordRole>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        /** @var Builder<DiscordRole> $query */
        $query = parent::getGlobalSearchEloquentQuery()->with(['guild']);

        return $query;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guild.name'];
    }

    /**
     * @param  DiscordRole  $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Guild' => $record->guild->name,
        ];
    }
}
