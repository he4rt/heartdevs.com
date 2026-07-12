<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord\Resources\DiscordMembers\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Discord\Resources\DiscordMembers\DiscordMemberResource;

class CreateDiscordMember extends CreateRecord
{
    protected static string $resource = DiscordMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
