<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\Meetings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('admin_id')
                    ->relationship('admin', 'name')
                    ->required(),
                RichEditor::make('content')
                    ->columnSpanFull(),
                Select::make('meeting_type_id')
                    ->relationship('meetingType', 'name')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->minLength(3)
                            ->maxLength(255)
                            ->required(),
                        Select::make('week_day')
                            ->options([
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                                7 => 'Sunday',
                            ])
                            ->required(),
                        TimePicker::make('start_at')
                            ->required(),
                    ])
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->after('starts_at'),
            ]);
    }
}
