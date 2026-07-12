<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\DiscordEventLogResource;

class EditDiscordEventLog extends EditRecord
{
    protected static string $resource = DiscordEventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
