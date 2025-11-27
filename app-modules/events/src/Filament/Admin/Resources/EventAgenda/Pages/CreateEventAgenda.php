<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\EventAgendaResource;

class CreateEventAgenda extends CreateRecord
{
    protected static string $resource = EventAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
