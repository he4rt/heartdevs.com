<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\Pages;

use Illuminate\Support\Facades\Date;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('UserTabs')
                ->tabs([
                    Tab::make('General')
                        ->schema([
                            Section::make()
                                ->secondary()
                                ->schema([
                                    TextInput::make('username')->required(),
                                    TextInput::make('name'),
                                    TextInput::make('email'),
                                    TextInput::make('password')->password(),
                                ]),
                        ]),

                    Tab::make('Address')
                        ->schema([
                            Section::make()
                                ->secondary()
                                ->relationship('address')
                                ->schema([
                                    TextInput::make('country'),
                                    TextInput::make('state'),
                                    TextInput::make('city'),
                                    TextInput::make('zip_code'),
                                ]),
                        ]),

                    Tab::make('Information')
                        ->schema([
                            Section::make()
                                ->secondary()
                                ->relationship('information')
                                ->schema([
                                    TextInput::make('name'),
                                    TextInput::make('nickname'),
                                    TextInput::make('linkedin_url'),
                                    TextInput::make('github_url'),
                                    DatePicker::make('birthdate'),
                                    Textarea::make('about'),
                                ]),
                        ]),

                    Tab::make('Gamefication')
                        ->schema([
                            TextEntry::make('character.tenant.name')
                                ->label('Tenant'),

                            TextEntry::make('character.reputation')
                                ->label('Reputation'),

                            TextEntry::make('character.experience')
                                ->label('Experience'),

                            TextEntry::make('character.daily_bonus_claimed_at')
                                ->label('Daily Bonus Claimed At')
                                ->formatStateUsing(fn ($state
                                ) => $state ? Date::parse($state)->format('Y-m-d') : ''),
                        ])->columns(4),
                    Tab::make('Providers')
                        ->schema([
                            Repeater::make('providers')
                                ->relationship('providers')
                                ->schema([
                                    TextEntry::make('provider')
                                        ->label('Provider'),

                                    TextEntry::make('provider_id')
                                        ->label('Provider ID'),

                                    TextEntry::make('email')
                                        ->label('Email'),
                                ])
                                ->columns(3)
                                ->addable(false)
                                ->deletable(false),
                        ]),
                ])
                ->columnSpan('full'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
