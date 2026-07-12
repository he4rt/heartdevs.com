<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordChannels\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordChannels\DiscordChannelResource;

class EditDiscordChannel extends EditRecord
{
    protected static string $resource = DiscordChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
