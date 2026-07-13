<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages;

use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;

class ViewDiscordMember extends ViewRecord
{
    protected static string $resource = DiscordMemberResource::class;
}
