<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscordEventLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('event_type')
                    ->label('Event Type'),

                TextEntry::make('guild_id')
                    ->label('Guild Id'),

                TextEntry::make('user_id')
                    ->label('User Id'),

                TextEntry::make('channel_id')
                    ->label('Channel Id'),

                TextEntry::make('payload')
                    ->label('Payload'),
            ]);
    }
}
