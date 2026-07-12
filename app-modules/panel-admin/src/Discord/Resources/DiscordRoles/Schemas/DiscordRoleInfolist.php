<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscordRoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Id'),

                TextEntry::make('guild.name')
                    ->label('Discord Guild Id'),

                TextEntry::make('discord_role_id')
                    ->label('Discord Role Id'),

                TextEntry::make('name')
                    ->label('Name'),

                TextEntry::make('color')
                    ->label('Color'),

                TextEntry::make('position')
                    ->label('Position'),

                TextEntry::make('permissions')
                    ->label('Permissions'),

                TextEntry::make('is_hoisted')
                    ->label('Is Hoisted'),

                TextEntry::make('is_mentionable')
                    ->label('Is Mentionable'),

                TextEntry::make('is_managed')
                    ->label('Is Managed'),

                TextEntry::make('icon')
                    ->label('Icon'),
            ]);
    }
}
