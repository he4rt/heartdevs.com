<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Filament\Resources\Events\EventResource;

final class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
