<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordRoles\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordRoles\DiscordRoleResource;

class EditDiscordRole extends EditRecord
{
    protected static string $resource = DiscordRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
