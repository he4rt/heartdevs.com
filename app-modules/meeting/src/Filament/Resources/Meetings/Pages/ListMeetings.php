<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\Meetings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Meeting\Filament\Resources\Meetings\MeetingResource;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
