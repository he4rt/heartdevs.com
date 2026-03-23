<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('User')
                    ->tabs([
                        Tab::make('General')
                            ->icon(Heroicon::User)
                            ->schema([
                                TextInput::make('username')
                                    ->required()
                                    ->minLength(3)
                                    ->maxLength(50)
                                    ->unique('users', 'username', ignoreRecord: true)
                                    ->autocomplete('off'),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique('users', 'name', ignoreRecord: true),
                                TextInput::make('email')
                                    ->nullable()
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->nullable()
                                    ->minLength(8)
                                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                                    ->dehydrated(fn ($state) => filled($state)),
                                Toggle::make('is_donator')
                                    ->default(false),
                            ]),
                        Tab::make('Address')
                            ->icon(Heroicon::MapPin)
                            ->schema([
                                Group::make()
                                    ->relationship('address')
                                    ->schema([
                                        TextInput::make('country')
                                            ->nullable()
                                            ->maxLength(255),
                                        TextInput::make('state')
                                            ->nullable()
                                            ->maxLength(255),
                                        TextInput::make('city')
                                            ->nullable()
                                            ->maxLength(255),
                                        TextInput::make('zip_code')
                                            ->nullable()
                                            ->maxLength(20),
                                    ]),
                            ]),
                        Tab::make('Information')
                            ->icon(Heroicon::InformationCircle)
                            ->schema([
                                Group::make()
                                    ->relationship('information')
                                    ->schema([
                                        TextInput::make('nickname')
                                            ->nullable(),
                                        TextInput::make('linkedin_url')
                                            ->nullable()
                                            ->url(),
                                        TextInput::make('github_url')
                                            ->nullable()
                                            ->url(),
                                        DatePicker::make('birthdate')
                                            ->nullable(),
                                        Textarea::make('about')
                                            ->nullable()
                                            ->rows(3),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
