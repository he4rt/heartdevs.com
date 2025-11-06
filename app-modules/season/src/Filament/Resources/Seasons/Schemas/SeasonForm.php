<?php

declare(strict_types=1);

namespace He4rt\Season\Filament\Resources\Seasons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeasonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('ended_at')
                    ->after('started_at'),
                TextInput::make('messages_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('participants_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('meeting_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('badges_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
