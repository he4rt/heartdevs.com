<?php

declare(strict_types=1);

namespace He4rt\Season\Filament\Resources\Seasons;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Season\Filament\Resources\Seasons\Pages\CreateSeason;
use He4rt\Season\Filament\Resources\Seasons\Pages\EditSeason;
use He4rt\Season\Filament\Resources\Seasons\Pages\ListSeasons;
use He4rt\Season\Filament\Resources\Seasons\Schemas\SeasonForm;
use He4rt\Season\Filament\Resources\Seasons\Tables\SeasonsTable;
use He4rt\Season\Models\Season;

class SeasonResource extends Resource
{
    protected static ?string $model = Season::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SeasonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeasonsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeasons::route('/'),
            'create' => CreateSeason::route('/create'),
            'edit' => EditSeason::route('/{record}/edit'),
        ];
    }
}
