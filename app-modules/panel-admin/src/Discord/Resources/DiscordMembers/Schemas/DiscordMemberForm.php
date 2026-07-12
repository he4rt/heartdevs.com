<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiscordMemberForm
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

                TextInput::make('discord_user_id')
                    ->label('Discord User Id')
                    ->required(),

                Select::make('external_identity_id')
                    ->label('External Identity Id')
                    ->relationship('externalIdentity', 'email')
                    ->searchable(),

                TextInput::make('username')
                    ->label('Username')
                    ->required(),

                TextInput::make('global_name')
                    ->label('Global Name'),

                TextInput::make('avatar')
                    ->label('Avatar'),

                TextInput::make('nickname')
                    ->label('Nickname'),

                Checkbox::make('is_bot')
                    ->label('Is Bot'),

                Checkbox::make('is_pending')
                    ->label('Is Pending'),

                TextInput::make('joined_at')
                    ->label('Joined At'),

                TextInput::make('premium_since')
                    ->label('Premium Since'),

                TextInput::make('communication_disabled_until')
                    ->label('Communication Disabled Until'),

                TextInput::make('left_at')
                    ->label('Left At'),
            ]);
    }
}
