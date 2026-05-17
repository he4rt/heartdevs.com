<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Events\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Events\Filament\Resources\Events\EventResource;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
