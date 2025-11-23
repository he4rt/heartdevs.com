<?php

declare(strict_types=1);

namespace He4rt\Message\Filament\Admin\Resources\Messages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider_id')
                    ->label('Provider')
                    ->relationship('provider', 'provider')
                    ->searchable()
                    ->required(),

                TextInput::make('channel_id')
                    ->label('Chanel')
                    ->nullable(),

                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->nullable(),

                TextInput::make('provider_message_id')
                    ->label('Message ID at Provider')
                    ->maxLength(100)
                    ->nullable(),

                Textarea::make('content')
                    ->label('Content')
                    ->rows(5)
                    ->required(),

                DateTimePicker::make('sent_at')
                    ->label('Sent at')
                    ->nullable(),

                TextInput::make('obtained_experience')
                    ->label('Experience Gained')
                    ->numeric()
                    ->nullable(),
            ]);
    }
}
