<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\ListExternalIdentities;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages\ViewExternalIdentity;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\RelationManagers\MessagesRelationManager;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Schemas\ExternalIdentityForm;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Tables\ExternalIdentitiesTable;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Widgets\ExternalIdentityStatsOverview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ExternalIdentityResource extends Resource
{
    protected static ?string $model = ExternalIdentity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'external_account_id';

    public static function form(Schema $schema): Schema
    {
        return ExternalIdentityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExternalIdentitiesTable::configure($table);
    }

    /**
     * @return array<class-string>
     */
    public static function getWidgets(): array
    {
        return [
            ExternalIdentityStatsOverview::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListExternalIdentities::route('/'),
            'edit' => ViewExternalIdentity::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
