<?php

namespace He4rt\Feedback\Filament\Admin\Resources\Feedback\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sender_id')
                    ->label('Sender')
                    ->relationship('sender', 'username')
                    ->searchable()
                    ->required(),

                Select::make('target_id')
                    ->label('Target')
                    ->relationship('target', 'username')
                    ->searchable()
                    ->required(),

                Select::make('tenant_id')
                    ->label('Target')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('type')
                    ->label('Type')
                    ->required()
                    ->maxLength(50),

                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(5)
                    ->maxLength(500),
            ]);
    }
}
