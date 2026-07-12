<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscordGuildForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('discord_guild_id')
                    ->label('Discord Guild Id')
                    ->required(),

                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                TextInput::make('icon')
                    ->label('Icon'),

                TextInput::make('description')
                    ->label('Description'),

                TextInput::make('member_count')
                    ->label('Member Count')
                    ->integer(),

                TextInput::make('premium_tier')
                    ->label('Premium Tier')
                    ->required()
                    ->integer(),

                TextInput::make('features')
                    ->label('Features')
                    ->required(),

                TextInput::make('synced_at')
                    ->label('Synced At'),
            ]);
    }
}
