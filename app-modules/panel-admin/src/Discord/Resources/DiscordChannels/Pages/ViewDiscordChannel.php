<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages;

use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\DiscordChannelResource;

class ViewDiscordChannel extends ViewRecord
{
    protected static string $resource = DiscordChannelResource::class;
}
