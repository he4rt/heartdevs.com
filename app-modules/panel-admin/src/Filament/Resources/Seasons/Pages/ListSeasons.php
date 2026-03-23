<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Seasons\SeasonResource;

class ListSeasons extends ListRecords
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
