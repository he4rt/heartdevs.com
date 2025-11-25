<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Shared\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use OtavioAraujo\FilamentSmartCep\Forms\Components\SmartCep;

final class UserAddressForm
{
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Location Information')
                    ->description('Basic address details')
                    ->schema([
                        SmartCep::make('zip_code')
                            ->label('ZIP Code')
                            ->placeholder('Enter your ZIP Code (e.g., 13000-000)')
                            ->mask('99999-999')
                            ->bindCityField('city')
                            ->bindStateCodeField('state')
                            ->bindCountryCodeField('country')
                            ->live()
                            ->columnSpan(1),

                        TextInput::make('country')
                            ->label('Country')
                            ->maxLength(4)
                            ->placeholder('Brazil')
                            ->columnSpan(1),

                        TextInput::make('state')
                            ->label('State')
                            ->live()
                            ->maxLength(4)
                            ->placeholder('São Paulo')
                            ->columnSpan(1),

                        TextInput::make('city')
                            ->label('City')
                            ->live()
                            ->placeholder('Campinas')
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
