<?php

declare(strict_types=1);

namespace He4rt\Meeting\Filament\Resources\MeetingTypes\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Meeting\Filament\Resources\MeetingTypes\MeetingTypeResource;

class CreateMeetingType extends CreateRecord
{
    protected static string $resource = MeetingTypeResource::class;
}
