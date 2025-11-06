<?php

declare(strict_types=1);

namespace He4rt\Tenant\Filament\Admin\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Schemas\TenantForm;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\Tables\TenantsTable;
use He4rt\Tenant\Models\Tenant;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
