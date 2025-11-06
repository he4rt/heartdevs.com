<?php

declare(strict_types=1);

namespace He4rt\Tenant\Filament\Admin\Resources\Tenants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\TenantResource;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
