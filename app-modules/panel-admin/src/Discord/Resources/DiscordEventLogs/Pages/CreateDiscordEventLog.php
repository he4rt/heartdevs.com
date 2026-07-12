<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\DiscordEventLogResource;

class CreateDiscordEventLog extends CreateRecord
{
    protected static string $resource = DiscordEventLogResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
