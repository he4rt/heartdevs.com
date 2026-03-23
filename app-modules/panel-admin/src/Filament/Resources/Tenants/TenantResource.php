<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Pages\CreateTenant;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Pages\EditTenant;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Pages\ListTenants;
use He4rt\PanelAdmin\Filament\Resources\Tenants\RelationManagers\MembersRelationManager;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Schemas\TenantForm;
use He4rt\PanelAdmin\Filament\Resources\Tenants\Tables\TenantsTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'Community';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
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
