<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscordChannelForm
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

                TextInput::make('discord_channel_id')
                    ->label('Discord Channel Id')
                    ->required(),

                Select::make('parent_id')
                    ->label('Parent Id')
                    ->relationship('parent', 'name')
                    ->searchable(),

                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                TextInput::make('type')
                    ->label('Type')
                    ->required(),

                TextInput::make('topic')
                    ->label('Topic'),

                TextInput::make('position')
                    ->label('Position')
                    ->required()
                    ->integer(),

                Checkbox::make('nsfw')
                    ->label('Nsfw'),

                TextInput::make('bitrate')
                    ->label('Bitrate')
                    ->integer(),

                TextInput::make('user_limit')
                    ->label('User Limit')
                    ->integer(),
            ]);
    }
}
