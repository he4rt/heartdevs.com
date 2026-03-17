<?php

declare(strict_types=1);

namespace He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\MeetingTypeResource;

class EditMeetingType extends EditRecord
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
