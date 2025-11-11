<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\MeetingTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Meeting\Filament\Resources\MeetingTypes\MeetingTypeResource;

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
