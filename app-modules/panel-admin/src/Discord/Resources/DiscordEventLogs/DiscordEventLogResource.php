<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\ListDiscordEventLogs;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\ViewDiscordEventLog;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas\DiscordEventLogInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Tables\DiscordEventLogsTable;

class DiscordEventLogResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;

    protected static ?string $model = DiscordEventLog::class;

    protected static ?string $slug = 'discord-event-logs';

    protected static ?string $recordTitleAttribute = 'event_type';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.event_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::discord.navigation.group_events');
    }

    public static function getModelLabel(): string
    {
        return __('panel-admin::discord.event_logs.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel-admin::discord.event_logs.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordEventLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordEventLogsTable::table($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDiscordEventLogs::route('/'),
            'view' => ViewDiscordEventLog::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
