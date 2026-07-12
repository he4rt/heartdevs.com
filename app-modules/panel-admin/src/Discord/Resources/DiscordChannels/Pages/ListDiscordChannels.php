<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\DiscordChannelResource;

class ListDiscordChannels extends ListRecords
{
    protected static string $resource = DiscordChannelResource::class;
}
