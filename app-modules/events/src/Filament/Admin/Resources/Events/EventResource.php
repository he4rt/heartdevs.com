<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\CreateEvent;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\EditEvent;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\ListEvents;
use He4rt\Events\Filament\Admin\Resources\Events\RelationManagers\AttendeesRelationManager;
use He4rt\Events\Filament\Admin\Resources\Events\RelationManagers\TalksRelationManager;
use He4rt\Events\Filament\Admin\Resources\Events\Schemas\EventForm;
use He4rt\Events\Filament\Admin\Resources\Events\Tables\EventsTable;
use He4rt\Events\Models\EventModel;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = EventModel::class;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calendar;

    protected static ?string $label = 'Events';

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TalksRelationManager::class,
            AttendeesRelationManager::class,
        ];
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
