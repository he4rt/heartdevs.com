<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExternalIdentityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tenant_id')
                    ->label('Tenant Id')
                    ->required()
                    ->integer(),

                TextInput::make('model_type')
                    ->label('Model Type')
                    ->required(),

                Select::make('model_id')
                    ->label('Model Id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('type')
                    ->label('Type')
                    ->required(),

                TextInput::make('provider')
                    ->label('Provider')
                    ->required(),

                TextInput::make('external_account_id')
                    ->label('External Account Id'),

                Select::make('connected_by')
                    ->label('Connected By')
                    ->relationship('connectedByUser', 'name')
                    ->searchable(),

                DatePicker::make('connected_at')
                    ->label('Connected Date'),

                DatePicker::make('disconnected_at')
                    ->label('Disconnected Date'),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->dateTime(),

                TextInput::make('email')
                    ->label('Email'),
            ]);
    }
}
