<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\EventModels\EventModelResource;

class CreateEventModel extends CreateRecord
{
    protected static string $resource = EventModelResource::class;
}
