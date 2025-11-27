<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Talks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Talks\Pages\CreateTalk;
use He4rt\Events\Filament\Admin\Resources\Talks\Pages\EditTalk;
use He4rt\Events\Filament\Admin\Resources\Talks\Pages\ListTalks;
use He4rt\Events\Filament\Admin\Resources\Talks\Schemas\TalkForm;
use He4rt\Events\Filament\Admin\Resources\Talks\Tables\TalksTable;
use He4rt\Events\Models\EventSubmission;
use UnitEnum;

class TalkResource extends Resource
{
    protected static ?string $model = EventSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Microphone;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $label = 'Submissions';

    public static function getNavigationBadge(): ?string
    {
        return (string) self::$model::count();
    }

    public static function form(Schema $schema): Schema
    {
        return TalkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TalksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalks::route('/'),
            'create' => CreateTalk::route('/create'),
            'edit' => EditTalk::route('/{record}/edit'),
        ];
    }
}
