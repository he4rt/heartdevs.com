<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\IntegrationDiscord\Models\DiscordEventLog;
use He4rt\PanelAdmin\Discord\DiscordCluster;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas\DiscordEventLogForm;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas\DiscordEventLogInfolist;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Tables\DiscordEventLogsTable;

class DiscordEventLogResource extends Resource
{
    protected static ?string $cluster = DiscordCluster::class;
    protected static ?string $model = DiscordEventLog::class;

    protected static ?string $slug = 'discord-event-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DiscordEventLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscordEventLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscordEventLogsTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => \He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\ListDiscordEventLogs::route('/'),
            'create' => \He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\CreateDiscordEventLog::route('/create'),
            'edit' => \He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages\EditDiscordEventLog::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
