<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\InteractionResource;

class ListInteractions extends ListRecords
{
    protected static string $resource = InteractionResource::class;
}
