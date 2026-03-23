<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\EventSubmissionResource;

class CreateEventSubmission extends CreateRecord
{
    protected static string $resource = EventSubmissionResource::class;
}
