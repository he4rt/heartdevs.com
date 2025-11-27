<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource;

class EditEventSubmissionSpeaker extends EditRecord
{
    protected static string $resource = EventSubmissionSpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
