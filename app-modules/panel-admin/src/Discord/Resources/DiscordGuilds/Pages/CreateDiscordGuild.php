<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class CreateDiscordGuild extends CreateRecord
{
    protected static string $resource = DiscordGuildResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
