<?php

namespace He4rt\User\Filament\Admin\Resources\Tenants\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\User\Filament\Admin\Resources\Tenants\TenantResource;

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
