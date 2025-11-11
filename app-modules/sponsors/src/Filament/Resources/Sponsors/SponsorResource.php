<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Filament\Resources\Sponsors;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Sponsors\Filament\Resources\Sponsors\Pages\CreateSponsor;
use He4rt\Sponsors\Filament\Resources\Sponsors\Pages\EditSponsor;
use He4rt\Sponsors\Filament\Resources\Sponsors\Pages\ListSponsors;
use He4rt\Sponsors\Filament\Resources\Sponsors\Schemas\SponsorForm;
use He4rt\Sponsors\Filament\Resources\Sponsors\Tables\SponsorsTable;
use He4rt\Sponsors\Models\Sponsor;
use UnitEnum;

class SponsorResource extends Resource
{
    protected static ?string $model = Sponsor::class;

    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SponsorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SponsorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSponsors::route('/'),
            'create' => CreateSponsor::route('/create'),
            'edit' => EditSponsor::route('/{record}/edit'),
        ];
    }
}
