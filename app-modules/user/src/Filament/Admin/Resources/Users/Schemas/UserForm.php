<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->description('Basic User Information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255),
                        TextInput::make('username')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->unique()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
