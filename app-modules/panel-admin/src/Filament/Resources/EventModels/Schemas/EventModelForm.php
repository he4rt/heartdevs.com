<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use He4rt\Events\Enums\EventTypeEnum;

class EventModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('tenant_id')
                    ->required()
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique('events', 'slug', ignoreRecord: true),
                RichEditor::make('description')
                    ->required(),
                Select::make('event_type')
                    ->required()
                    ->options(EventTypeEnum::class),
                TextInput::make('location')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255),
                TextInput::make('max_attendees')
                    ->required()
                    ->integer()
                    ->minValue(1),
                Toggle::make('active'),
                DateTimePicker::make('event_at')
                    ->required(),
                DateTimePicker::make('start_at')
                    ->required(),
                DateTimePicker::make('end_at')
                    ->required()
                    ->after('start_at'),
            ]);
    }
}
