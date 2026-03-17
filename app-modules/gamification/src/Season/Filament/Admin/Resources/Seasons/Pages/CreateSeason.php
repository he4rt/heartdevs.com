<?php

declare(strict_types=1);

namespace He4rt\Gamification\Season\Filament\Admin\Resources\Seasons\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Gamification\Season\Filament\Admin\Resources\Seasons\SeasonResource;

class CreateSeason extends CreateRecord
{
    protected static string $resource = SeasonResource::class;
}
