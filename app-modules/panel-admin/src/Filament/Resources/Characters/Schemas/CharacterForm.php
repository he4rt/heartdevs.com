<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CharacterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('experience')
                    ->required()
                    ->integer()
                    ->minValue(0),

                TextInput::make('reputation')
                    ->required()
                    ->integer(),
            ]);
    }
}
