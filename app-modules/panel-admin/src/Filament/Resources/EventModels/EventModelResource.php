<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Models\EventModel;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Pages\CreateEventModel;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Pages\EditEventModel;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Pages\ListEventModels;
use He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers\AgendaRelationManager;
use He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers\AttendeesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers\TalksRelationManager;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Schemas\EventModelForm;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Tables\EventModelsTable;
use UnitEnum;

class EventModelResource extends Resource
{
    protected static ?string $model = EventModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    public static function form(Schema $schema): Schema
    {
        return EventModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttendeesRelationManager::class,
            TalksRelationManager::class,
            AgendaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventModels::route('/'),
            'create' => CreateEventModel::route('/create'),
            'edit' => EditEventModel::route('/{record}/edit'),
        ];
    }
}
