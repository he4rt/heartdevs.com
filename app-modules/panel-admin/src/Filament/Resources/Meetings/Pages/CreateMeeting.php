<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Meetings\MeetingResource;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;
}
