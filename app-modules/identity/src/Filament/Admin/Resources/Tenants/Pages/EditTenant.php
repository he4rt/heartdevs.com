<?php

declare(strict_types=1);

namespace He4rt\Identity\Filament\Admin\Resources\Tenants\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Identity\Filament\Admin\Resources\Tenants\TenantResource;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
