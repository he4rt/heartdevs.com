<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages;

use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\DiscordEventLogResource;

class ViewDiscordEventLog extends ViewRecord
{
    protected static string $resource = DiscordEventLogResource::class;
}
