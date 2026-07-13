<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Discord\Resources\DiscordEventLogs\DiscordEventLogResource;

class ListDiscordEventLogs extends ListRecords
{
    protected static string $resource = DiscordEventLogResource::class;
}
