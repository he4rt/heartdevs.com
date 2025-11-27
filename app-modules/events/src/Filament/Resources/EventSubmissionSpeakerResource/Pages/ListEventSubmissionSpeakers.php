<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Filament\Resources\EventSubmissionSpeakerResource;

class ListEventSubmissionSpeakers extends ListRecords
{
    protected static string $resource = EventSubmissionSpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
