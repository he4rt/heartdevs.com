<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
