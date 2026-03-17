<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Filament\Resources\MeetingTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Pages\CreateMeetingType;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Pages\EditMeetingType;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Pages\ListMeetingTypes;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Schemas\MeetingTypeForm;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Tables\MeetingTypesTable;
use He4rt\Community\Meeting\Models\MeetingType;
use UnitEnum;

class MeetingTypeResource extends Resource
{
    protected static ?string $model = MeetingType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Meetings';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MeetingTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeetingTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingTypes::route('/'),
            'create' => CreateMeetingType::route('/create'),
            'edit' => EditMeetingType::route('/{record}/edit'),
        ];
    }
}
