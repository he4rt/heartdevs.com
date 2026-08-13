<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource;

class ListUpcomingEvents extends ListRecords
{
    protected static string $resource = UpcomingEventResource::class;

    /**
     * @return CreateAction[]
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
