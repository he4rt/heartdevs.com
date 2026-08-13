<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource;

class CreateUpcomingEvent extends CreateRecord
{
    protected static string $resource = UpcomingEventResource::class;
}
