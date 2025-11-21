<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\Schemas;

use App\Filament\Shared\RelationManagers\EventsRelationManager;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                Flex::make([
                    Section::make('Informações Gerais')
                        ->schema([
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

                            Select::make('event_type')
                                ->label('Event Type')
                                ->enum(EventTypeEnum::class)
                                ->options(EventTypeEnum::class)
                                ->required(),
                        ])
                        ->columns(2),
                ]),

                Flex::make([
                    Section::make('Local e Capacidade')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('location')
                                    ->label('Location')
                                    ->minLength(5)
                                    ->maxLength(255)
                                    ->required(),

                                TextInput::make('max_attendees')
                                    ->numeric()
                                    ->minValue(1)
                                    ->label('Max Attendees')
                                    ->required(),
                            ]),
                        ]),
                    Section::make('Status')
                        ->schema([
                            Toggle::make('active')
                                ->label('Active')
                                ->required(),
                        ]),
                ]),

                Section::make('Datas e Horários')
                    ->schema([
                        Grid::make(3)->schema([
                            DateTimePicker::make('event_at')
                                ->label('Event Date')
                                ->required(),

                            DateTimePicker::make('start_at')
                                ->label('Start Hour')
                                ->required(),

                            DateTimePicker::make('end_at')
                                ->label('End Hour')
                                ->after('start_at')
                                ->required(),
                        ]),
                    ]),

                Section::make('Conteúdo')
                    ->schema([
                        RichEditor::make('description')
                            ->label('Description')
                            ->minLength(5)
                            ->maxLength(255)
                            ->required(),
                    ]),
            ])
            ->columns(1);
    }
}
