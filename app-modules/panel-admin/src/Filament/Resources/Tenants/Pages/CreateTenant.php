<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Tenants\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Tenants\TenantResource;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
