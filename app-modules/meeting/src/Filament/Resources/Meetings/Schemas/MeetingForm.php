<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\Meetings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Query\Builder;

class MeetingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Administration Area')
                    ->description('Tenant and Administration')
                    ->schema([
                        Select::make('tenant_id')
                            ->preload()
                            ->relationship(
                                name: 'tenant',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                            )
                            ->required(),
                        Select::make('admin_id')
                            ->preload()
                            ->relationship(
                                name: 'admin',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                            )
                            ->required(),
                    ]),
                Section::make('Meeting Data')
                    ->description('Choose the Start and End')
                    ->schema([
                        TimePicker::make('starts_at')
                            ->hoursStep(2)
                            ->required(),
                        TimePicker::make('ends_at')
                            ->hoursStep(2)
                            ->after('starts_at')
                            ->required(),
                    ]),
                Section::make('Meeting Fields')
                    ->description('Meeting Region')
                    ->schema([
                        RichEditor::make('content')
                            ->columnSpanFull(),
                        Select::make('meeting_type_id')
                            ->preload()
                            ->relationship(
                                name: 'meetingType',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->limit(10)
                            )
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
