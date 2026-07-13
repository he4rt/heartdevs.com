<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordChannel;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\ListDiscordChannels;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages\ViewDiscordChannel;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas\DiscordChannelInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Tables\DiscordChannelsTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DiscordChannelResource extends Resource
{
    protected static ?string $model = DiscordChannel::class;

    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $slug = 'discord-channels';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.channels');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_server');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::discord.channels.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::discord.channels.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordChannelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordChannelsTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDiscordChannels::route('/'),
            'view' => ViewDiscordChannel::route('/{record}'),
        ];
    }

    /**
     * @return Builder<DiscordChannel>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        /** @var Builder<DiscordChannel> $query */
        $query = parent::getGlobalSearchEloquentQuery()->with(['guild', 'parent']);

        return $query;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guild.name', 'parent.name'];
    }

    /**
     * @param  DiscordChannel  $record
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [
            'Guild' => $record->guild->name,
        ];

        if ($record->parent !== null) {
            $details['Parent'] = $record->parent->name;
        }

        return $details;
    }
}
