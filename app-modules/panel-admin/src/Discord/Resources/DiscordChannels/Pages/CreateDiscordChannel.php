<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\DiscordChannelResource;

class CreateDiscordChannel extends CreateRecord
{
    protected static string $resource = DiscordChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
