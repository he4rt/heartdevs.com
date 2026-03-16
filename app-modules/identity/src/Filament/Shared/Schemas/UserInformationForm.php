<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Shared\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserInformationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->description('Basic profile details and social links.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->placeholder('Enter your full name')
                            ->maxLength(255),

                        TextInput::make('nickname')
                            ->label('Nickname')
                            ->placeholder('How do you like to be called?')
                            ->maxLength(255),

                        DatePicker::make('birthdate')
                            ->label('Birthdate')
                            ->placeholder('Select your birth date'),

                        RichEditor::make('about')
                            ->label('About')
                            ->placeholder('Write a short description about yourself...')
                            ->columnSpanFull(),

                        TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->placeholder('https://linkedin.com/in/username')
                            ->url(),

                        TextInput::make('github_url')
                            ->label('GitHub URL')
                            ->placeholder('https://github.com/username')
                            ->url(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
