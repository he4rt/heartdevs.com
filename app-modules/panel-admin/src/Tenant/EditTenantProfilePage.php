<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Tenant;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditTenantProfilePage extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Tenant Settings';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                        ]),
                        TextInput::make('domain')
                            ->maxLength(255),
                        Toggle::make('active')
                            ->default(true),
                    ]),
                Section::make('Connections')
                    ->schema([
                        Livewire::make('connection-hub'),
                    ]),
            ]);
    }
}
