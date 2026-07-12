<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscordChannelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('guild.name')
                    ->label('Discord Guild Id'),

                TextEntry::make('discord_channel_id')
                    ->label('Discord Channel Id'),

                TextEntry::make('parent.name')
                    ->label('Parent Id'),

                TextEntry::make('name')
                    ->label('Name'),

                TextEntry::make('type')
                    ->label('Type'),

                TextEntry::make('topic')
                    ->label('Topic'),

                TextEntry::make('position')
                    ->label('Position'),

                TextEntry::make('nsfw')
                    ->label('Nsfw'),

                TextEntry::make('bitrate')
                    ->label('Bitrate'),

                TextEntry::make('user_limit')
                    ->label('User Limit'),
            ]);
    }
}
