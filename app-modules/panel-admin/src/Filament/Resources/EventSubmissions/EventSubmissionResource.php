<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Models\EventSubmission;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages\CreateEventSubmission;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages\EditEventSubmission;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages\ListEventSubmissions;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\RelationManagers\SpeakersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Schemas\EventSubmissionForm;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Tables\EventSubmissionsTable;
use UnitEnum;

class EventSubmissionResource extends Resource
{
    protected static ?string $model = EventSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMicrophone;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Talk';

    protected static ?string $pluralModelLabel = 'Talks';

    public static function form(Schema $schema): Schema
    {
        return EventSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SpeakersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventSubmissions::route('/'),
            'create' => CreateEventSubmission::route('/create'),
            'edit' => EditEventSubmission::route('/{record}/edit'),
        ];
    }
}
