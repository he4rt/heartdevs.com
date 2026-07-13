<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class ListDiscordGuilds extends ListRecords
{
    protected static string $resource = DiscordGuildResource::class;
}
