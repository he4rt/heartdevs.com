<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\MeetingTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\MeetingTypes\MeetingTypeResource;

class ListMeetingTypes extends ListRecords
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
