<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\EventModels\EventModelResource;

class EditEventModel extends EditRecord
{
    protected static string $resource = EventModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
