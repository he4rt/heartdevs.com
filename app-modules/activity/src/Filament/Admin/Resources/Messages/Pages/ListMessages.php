<?php

declare(strict_types=1);

namespace He4rt\Activity\Filament\Admin\Resources\Messages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Activity\Filament\Admin\Resources\Messages\MessageResource;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
