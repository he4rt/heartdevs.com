<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\EventSubmissionResource;

class EditEventSubmission extends EditRecord
{
    protected static string $resource = EventSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
