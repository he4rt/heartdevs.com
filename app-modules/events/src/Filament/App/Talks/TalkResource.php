<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\Talks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Filament\App\Talks\Pages\CreateTalk;
use He4rt\Events\Filament\App\Talks\Pages\ListTalks;
use He4rt\Events\Filament\App\Talks\Schemas\TalkForm;
use He4rt\Events\Filament\App\Talks\Tables\TalksTable;
use He4rt\Events\Models\EventSubmission;

class TalkResource extends Resource
{
    protected static ?string $model = EventSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Microphone;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

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
        ];
    }
}
