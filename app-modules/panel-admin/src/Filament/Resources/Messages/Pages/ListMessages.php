<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Messages\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Messages\MessageResource;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;
}
