<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives;

use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\CreateRetrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\EditRetrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages\ListRetrospectives;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Schemas\RetrospectiveForm;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Tables\RetrospectivesTable;

class RetrospectiveResource extends Resource
{
    protected static ?string $model = Retrospective::class;

    protected static ?string $slug = 'retrospectives';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'retrospectiva';
    }

    public static function getPluralModelLabel(): string
    {
        return 'retrospectivas';
    }

    public static function form(Schema $schema): Schema
    {
        return RetrospectiveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetrospectivesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRetrospectives::route('/'),
            'create' => CreateRetrospective::route('/create'),
            'edit' => EditRetrospective::route('/{record}/edit'),
        ];
    }
}
