<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscordEventLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event_type')
                    ->label('Event Type')
                    ->required(),

                TextInput::make('guild_id')
                    ->label('Guild Id'),

                TextInput::make('user_id')
                    ->label('User Id'),

                TextInput::make('channel_id')
                    ->label('Channel Id'),

                TextInput::make('payload')
                    ->label('Payload')
                    ->required(),
            ]);
    }
}
