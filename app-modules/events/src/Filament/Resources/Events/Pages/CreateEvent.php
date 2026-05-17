<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Events\Filament\Resources\Events\EventResource;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
