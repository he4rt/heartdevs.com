<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Agenda\Resources\UpcomingEventResource;

class EditUpcomingEvent extends EditRecord
{
    protected static string $resource = UpcomingEventResource::class;

    /**
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
