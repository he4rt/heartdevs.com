<?php

declare(strict_types=1);

namespace He4rt\Tenant\Filament\Admin\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('slug'),
                TextInput::make('owner_id'),
                TextInput::make('active'),
            ]);
    }
}
