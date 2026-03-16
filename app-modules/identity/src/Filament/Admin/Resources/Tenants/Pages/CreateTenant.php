<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Identity\Filament\Admin\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
