<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordMember;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas\DiscordMemberForm;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas\DiscordMemberInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Tables\DiscordMembersTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscordMemberResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;
    protected static ?string $model = DiscordMember::class;

    protected static ?string $slug = 'discord-members';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscordMemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordMembersTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\ListDiscordMembers::route('/'),
            'create' => \He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\CreateDiscordMember::route('/create'),
            'edit' => \He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages\EditDiscordMember::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<DiscordMember>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['guild', 'externalIdentity']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['guild.name', 'externalIdentity.email'];
    }

    /**
     * @param  DiscordMember  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->guild) {
            $details['Guild'] = $record->guild->name;
        }

        if ($record->externalIdentity) {
            $details['ExternalIdentity'] = $record->externalIdentity->email;
        }

        return $details;
    }
}
