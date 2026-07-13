<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages;

use Filament\Resources\Pages\ViewRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\DiscordRoleResource;

class ViewDiscordRole extends ViewRecord
{
    protected static string $resource = DiscordRoleResource::class;
}
