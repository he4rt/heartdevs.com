<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Voices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\PanelAdmin\Filament\Resources\Voices\Pages\ListVoices;
use He4rt\PanelAdmin\Filament\Resources\Voices\Tables\VoicesTable;
use UnitEnum;

class VoiceResource extends Resource
{
    protected static ?string $model = Voice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static string|UnitEnum|null $navigationGroup = 'Activity';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Voice Activity';

    protected static ?string $pluralModelLabel = 'Voice Activities';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return VoicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoices::route('/'),
        ];
    }
}
