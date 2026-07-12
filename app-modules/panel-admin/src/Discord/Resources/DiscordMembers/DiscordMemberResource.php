<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\ListDiscordMembers;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\ViewDiscordMember;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas\DiscordMemberInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Tables\DiscordMembersTable;
use Illuminate\Database\Eloquent\Model;

class DiscordMemberResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $model = DiscordMember::class;

    protected static ?string $slug = 'discord-members';

    protected static ?string $recordTitleAttribute = 'username';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.members');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_server');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::discord.members.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::discord.members.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordMembersTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDiscordMembers::route('/'),
            'view' => ViewDiscordMember::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['username', 'global_name', 'nickname'];
    }

    /**
     * @param  DiscordMember  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->global_name) {
            $details[__('panel-admin::discord.members.fields.global_name')] = $record->global_name;
        }

        if ($record->nickname) {
            $details[__('panel-admin::discord.members.fields.nickname')] = $record->nickname;
        }

        return $details;
    }
}
