<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Community\Meeting\Models\Meeting;
use He4rt\PanelAdmin\Filament\Resources\Meetings\Pages\CreateMeeting;
use He4rt\PanelAdmin\Filament\Resources\Meetings\Pages\EditMeeting;
use He4rt\PanelAdmin\Filament\Resources\Meetings\Pages\ListMeetings;
use He4rt\PanelAdmin\Filament\Resources\Meetings\RelationManagers\ParticipantsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Meetings\Schemas\MeetingForm;
use He4rt\PanelAdmin\Filament\Resources\Meetings\Tables\MeetingsTable;
use UnitEnum;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Meetings';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MeetingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetings::route('/'),
            'create' => CreateMeeting::route('/create'),
            'edit' => EditMeeting::route('/{record}/edit'),
        ];
    }
}
