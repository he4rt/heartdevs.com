<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Gamification\Character\Models\Character;
use He4rt\PanelAdmin\Filament\Resources\Characters\Pages\EditCharacter;
use He4rt\PanelAdmin\Filament\Resources\Characters\Pages\ListCharacters;
use He4rt\PanelAdmin\Filament\Resources\Characters\RelationManagers\BadgesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Characters\RelationManagers\PastSeasonsRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Characters\Schemas\CharacterForm;
use He4rt\PanelAdmin\Filament\Resources\Characters\Tables\CharactersTable;
use UnitEnum;

class CharacterResource extends Resource
{
    protected static ?string $model = Character::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|null|UnitEnum $navigationGroup = 'Gamification';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CharacterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CharactersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BadgesRelationManager::class,
            PastSeasonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharacters::route('/'),
            'edit' => EditCharacter::route('/{record}/edit'),
        ];
    }
}
