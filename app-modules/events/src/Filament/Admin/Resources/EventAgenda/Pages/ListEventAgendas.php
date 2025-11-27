<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\EventAgendaResource;

class ListEventAgendas extends ListRecords
{
    protected static string $resource = EventAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
