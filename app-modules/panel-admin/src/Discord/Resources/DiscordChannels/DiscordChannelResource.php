<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas\DiscordChannelForm;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas\DiscordChannelInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Tables\DiscordChannelsTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscordChannelResource extends Resource
{
    protected static ?string $model = DiscordChannel::class;

    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $slug = 'discord-channels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscordChannelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordChannelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordChannelsTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\ListDiscordChannels::route('/'),
            'create' => \He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\CreateDiscordChannel::route('/create'),
            'edit' => \He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\EditDiscordChannel::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<DiscordChannel>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['guild', 'parent']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guild.name', 'parent.name'];
    }

    /**
     * @param  DiscordChannel  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->guild) {
            $details['Guild'] = $record->guild->name;
        }

        if ($record->parent) {
            $details['Parent'] = $record->parent->name;
        }

        return $details;
    }
}
