<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;

class ListDiscordMembers extends ListRecords
{
    protected static string $resource = DiscordMemberResource::class;
}
