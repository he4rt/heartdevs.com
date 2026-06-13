<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Resources\TwitchEventLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Twitch\Resources\TwitchEventLogResource;

class ListTwitchEventLogs extends ListRecords
{
    protected static string $resource = TwitchEventLogResource::class;
}
