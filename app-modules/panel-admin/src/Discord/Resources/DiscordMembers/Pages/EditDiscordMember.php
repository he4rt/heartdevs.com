<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;

class EditDiscordMember extends EditRecord
{
    protected static string $resource = DiscordMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
