<?php

declare(strict_types=1);

namespace He4rt\Tenant\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('slug'),
                Select::make('owner_id')
                    ->relationship('owner', 'name'),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
