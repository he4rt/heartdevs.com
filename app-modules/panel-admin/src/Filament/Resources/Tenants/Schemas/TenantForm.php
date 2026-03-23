<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(100)
                    ->unique('tenants', 'slug', ignoreRecord: true),
                TextInput::make('domain')
                    ->nullable()
                    ->maxLength(255),
                Select::make('owner_id')
                    ->relationship('owner', 'username')
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
