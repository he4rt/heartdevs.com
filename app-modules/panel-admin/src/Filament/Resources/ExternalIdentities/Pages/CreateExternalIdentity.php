<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;

class CreateExternalIdentity extends CreateRecord
{
    protected static string $resource = ExternalIdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
