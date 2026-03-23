<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventSubmissions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\EventSubmissions\EventSubmissionResource;

class ListEventSubmissions extends ListRecords
{
    protected static string $resource = EventSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
