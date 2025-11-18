<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Events\Filament\Admin\Resources\Events\EventResource;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
