<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Meetings\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Meetings\MeetingResource;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
