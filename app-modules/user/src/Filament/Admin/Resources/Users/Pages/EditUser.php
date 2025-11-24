<?php

declare(strict_types=1);

namespace He4rt\User\Filament\Admin\Resources\Users\Pages;

use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use He4rt\User\Filament\Admin\Resources\Users\Schemas\UserForm;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;
use He4rt\User\Filament\Shared\Schemas\UserAddressForm;
use He4rt\User\Filament\Shared\Schemas\UserInformationForm;
use Illuminate\Support\Facades\Date;

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
                            Section::make('General Information')
                                ->description('Basic user details')
                                ->schema(fn (Schema $schema) => UserForm::configure($schema)),
                        ]),

                    Tab::make('Address')
                        ->schema(fn (Schema $schema) => UserAddressForm::form($schema)),

                    Tab::make('Information')
                        ->schema([
                            Section::make('Information')
                                ->description('Additional profile information')
                                ->relationship('information')
                                ->schema(fn (Schema $schema) => UserInformationForm::configure($schema)),
                        ]),

                    Tab::make('Gamefication')
                        ->schema([
                            Section::make('Gamefication')
                                ->description('Player and progression data')
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
                        ]),
                    Tab::make('Providers')
                        ->schema([
                            Section::make('Providers')
                                ->description('Connected OAuth providers')
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
