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
                TextInput::make('username')
                    ->label('Nome de usuário')
                    ->placeholder('ex.: joao123')
                    ->required()
                    ->minLength(3)
                    ->maxLength(50),

                TextInput::make('name')
                    ->label('Nome completo')
                    ->placeholder('Nome e sobrenome')
                    ->required()
                    ->maxLength(100),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->placeholder('ex.: usuario@dominio.com')
                    ->required()
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->placeholder('Mínimo de 8 caracteres')
                    ->required()
                    ->minLength(8),
            ]);
    }
}
