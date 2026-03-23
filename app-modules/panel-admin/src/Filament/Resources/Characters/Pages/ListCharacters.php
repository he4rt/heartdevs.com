<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Characters\CharacterResource;

class ListCharacters extends ListRecords
{
    protected static string $resource = CharacterResource::class;
}
