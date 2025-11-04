<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\EventTypeEnum;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Title')
                    ->minLength(5)
                    ->maxLength(255)
                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state)))
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->minLength(5)
                    ->maxLength(255)
                    ->autosize()
                    ->required(),
                TextInput::make('location')
                    ->label('Location')
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
                Select::make('event_type')
                    ->label('Event Type')
                    ->enum(EventTypeEnum::class)
                    ->options([EventTypeEnum::class])
                    ->required(),
                Select::make('active')
                    ->label('Active')
                    ->options([
                        'true' => true,
                        'false' => false,
                    ])
                    ->required(),
                DateTimePicker::make('event_at')
                    ->label('Event Date')
                    ->required(),
                DateTimePicker::make('start_at')
                    ->label('Event Start Hour')
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Event End Hour')
                    ->required(),
            ]);
    }
}
