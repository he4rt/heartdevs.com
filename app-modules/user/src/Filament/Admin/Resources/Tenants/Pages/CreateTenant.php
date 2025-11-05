<?php

namespace He4rt\User\Filament\Admin\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\User\Filament\Admin\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
