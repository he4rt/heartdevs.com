<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscordMemberInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('guild.name')
                    ->label('Discord Guild Id'),

                TextEntry::make('discord_user_id')
                    ->label('Discord User Id'),

                TextEntry::make('externalIdentity.email')
                    ->label('External Identity Id'),

                TextEntry::make('username')
                    ->label('Username'),

                TextEntry::make('global_name')
                    ->label('Global Name'),

                TextEntry::make('avatar')
                    ->label('Avatar'),

                TextEntry::make('nickname')
                    ->label('Nickname'),

                TextEntry::make('is_bot')
                    ->label('Is Bot'),

                TextEntry::make('is_pending')
                    ->label('Is Pending'),

                TextEntry::make('joined_at')
                    ->label('Joined At'),

                TextEntry::make('premium_since')
                    ->label('Premium Since'),

                TextEntry::make('communication_disabled_until')
                    ->label('Communication Disabled Until'),

                TextEntry::make('left_at')
                    ->label('Left At'),
            ]);
    }
}
