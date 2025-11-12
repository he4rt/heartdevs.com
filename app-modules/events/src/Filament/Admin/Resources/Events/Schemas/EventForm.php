<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                Select::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('title')
                    ->label('Title')
                    ->minLength(5)
                    ->maxLength(255)
                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state)))
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                RichEditor::make('description')
                    ->label('Description')
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
                TextInput::make('location')
                    ->label('Location')
                    ->minLength(5)
                    ->maxLength(255)
                    ->required(),
                Select::make('event_type')
                    ->label('EventModel Type')
                    ->enum(EventTypeEnum::class)
                    ->options(EventTypeEnum::class)
                    ->required(),
                TextInput::make('max_attendees')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('active')
                    ->label('Active')
                    ->options([
                        true => 'Enabled',
                        false => 'Disabled',
                    ])
                    ->required(),
                DateTimePicker::make('event_at')
                    ->label('EventModel Date')
                    ->required(),
                DateTimePicker::make('start_at')
                    ->label('EventModel Start Hour')
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('EventModel End Hour')
                    ->after('start_at')
                    ->required(),
            ]);
    }
}
