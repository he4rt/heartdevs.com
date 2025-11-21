<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\Meetings;

use App\Filament\Shared\RelationManagers\MembersRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Meeting\Filament\Resources\Meetings\Pages\CreateMeeting;
use He4rt\Meeting\Filament\Resources\Meetings\Pages\EditMeeting;
use He4rt\Meeting\Filament\Resources\Meetings\Pages\ListMeetings;
use He4rt\Meeting\Filament\Resources\Meetings\Schemas\MeetingForm;
use He4rt\Meeting\Filament\Resources\Meetings\Tables\MeetingsTable;
use He4rt\Meeting\Models\Meeting;
use UnitEnum;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Meetings';

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
            MembersRelationManager::class,
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
