<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordRole;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas\DiscordRoleForm;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas\DiscordRoleInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Tables\DiscordRolesTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscordRoleResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;
    protected static ?string $model = DiscordRole::class;

    protected static ?string $slug = 'discord-roles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscordRoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordRoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordRolesTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\ListDiscordRoles::route('/'),
            'create' => \He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\CreateDiscordRole::route('/create'),
            'edit' => \He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages\EditDiscordRole::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<DiscordRole>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['guild']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guild.name'];
    }

    /**
     * @param  DiscordRole  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->guild) {
            $details['Guild'] = $record->guild->name;
        }

        return $details;
    }
}
