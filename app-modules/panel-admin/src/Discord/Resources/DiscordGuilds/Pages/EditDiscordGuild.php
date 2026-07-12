<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordGuilds\DiscordGuildResource;

class EditDiscordGuild extends EditRecord
{
    protected static string $resource = DiscordGuildResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
