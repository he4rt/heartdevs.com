<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;

class ListExternalIdentities extends ListRecords
{
    protected static string $resource = ExternalIdentityResource::class;
}
