<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('username'),
                TextInput::make('email'),
                TextInput::make('password'),
            ]);
    }
}
