<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscordGuildInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('discord_guild_id')
                    ->label('Discord Guild Id'),

                TextEntry::make('name')
                    ->label('Name'),

                TextEntry::make('icon')
                    ->label('Icon'),

                TextEntry::make('description')
                    ->label('Description'),

                TextEntry::make('member_count')
                    ->label('Member Count'),

                TextEntry::make('premium_tier')
                    ->label('Premium Tier'),

                TextEntry::make('features')
                    ->label('Features'),

                TextEntry::make('synced_at')
                    ->label('Synced At'),
            ]);
    }
}
