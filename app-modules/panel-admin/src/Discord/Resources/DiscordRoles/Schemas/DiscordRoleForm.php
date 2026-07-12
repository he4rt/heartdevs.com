<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscordRoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('discord_guild_id')
                    ->label('Discord Guild Id')
                    ->relationship('guild', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('discord_role_id')
                    ->label('Discord Role Id')
                    ->required(),

                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                TextInput::make('color')
                    ->label('Color')
                    ->required()
                    ->integer(),

                TextInput::make('position')
                    ->label('Position')
                    ->required()
                    ->integer(),

                TextInput::make('permissions')
                    ->label('Permissions')
                    ->required()
                    ->integer(),

                Checkbox::make('is_hoisted')
                    ->label('Is Hoisted'),

                Checkbox::make('is_mentionable')
                    ->label('Is Mentionable'),

                Checkbox::make('is_managed')
                    ->label('Is Managed'),

                TextInput::make('icon')
                    ->label('Icon'),
            ]);
    }
}
