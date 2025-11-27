<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource;

class CreateEventSubmissionSpeaker extends CreateRecord
{
    protected static string $resource = EventSubmissionSpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
