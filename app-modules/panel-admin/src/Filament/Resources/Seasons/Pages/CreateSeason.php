<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Filament\Resources\Seasons\SeasonResource;

class CreateSeason extends CreateRecord
{
    protected static string $resource = SeasonResource::class;
}
