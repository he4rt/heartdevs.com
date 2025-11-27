<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\EventAgenda\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Events\Filament\Admin\Resources\EventAgenda\EventAgendaResource;

class EditEventAgenda extends EditRecord
{
    protected static string $resource = EventAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
