<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Filament\Resources\Badges;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Gamification\Badge\Filament\Resources\Badges\Pages\CreateBadge;
use He4rt\Gamification\Badge\Filament\Resources\Badges\Pages\EditBadge;
use He4rt\Gamification\Badge\Filament\Resources\Badges\Pages\ListBadges;
use He4rt\Gamification\Badge\Filament\Resources\Badges\Schemas\BadgeForm;
use He4rt\Gamification\Badge\Filament\Resources\Badges\Tables\BadgesTable;
use He4rt\Gamification\Badge\Models\Badge;
use UnitEnum;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gamefication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BadgeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BadgesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBadges::route('/'),
            'create' => CreateBadge::route('/create'),
            'edit' => EditBadge::route('/{record}/edit'),
        ];
    }
}
