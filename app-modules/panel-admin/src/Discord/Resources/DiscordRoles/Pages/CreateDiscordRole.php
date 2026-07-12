<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\DiscordRoleResource;

class CreateDiscordRole extends CreateRecord
{
    protected static string $resource = DiscordRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
