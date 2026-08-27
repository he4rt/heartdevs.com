<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\DiscordRoleResource;

class ListDiscordRoles extends ListRecords
{
    protected static string $resource = DiscordRoleResource::class;
}
