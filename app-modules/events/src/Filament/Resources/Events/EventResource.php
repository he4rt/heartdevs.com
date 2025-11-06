<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Filament\Resources\Events\Pages\CreateEvent;
use He4rt\Events\Filament\Resources\Events\Pages\EditEvent;
use He4rt\Events\Filament\Resources\Events\Pages\ListEvents;
use He4rt\Events\Filament\Resources\Events\Schemas\EventForm;
use He4rt\Events\Filament\Resources\Events\Tables\EventsTable;
use He4rt\Events\Models\EventModel as Event;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
